<?php

namespace App\Jobs;

use App\Models\BlastRecipient;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10; // 10 seconds between retries
    public int $timeout = 60;

    public function __construct(
        public BlastRecipient $recipient
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        $blast = $this->recipient->blastSchedule;
        $device = $blast->whatsappDevice;

        if (!$device || !$device->isConnected()) {
            $this->markFailed('WhatsApp device is not connected');
            return;
        }

        // Build chat ID
        $chatId = $this->recipient->getChatId();

        // Prepare media URL if exists
        $mediaUrl = null;
        if ($blast->hasMedia()) {
            $mediaUrl = $blast->getMediaUrl();
        }

        // Send message
        $result = $whatsappService->sendMessage(
            $device->instance_id,
            $chatId,
            $blast->message,
            $mediaUrl
        );

        if ($result['success']) {
            $this->markSent();
        } else {
            $this->markFailed($result['error'] ?? 'Unknown error');
        }
    }

    protected function markSent(): void
    {
        $this->recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);

        // Update blast count
        $blast = $this->recipient->blastSchedule;
        $blast->increment('sent_count');

        // Check if all messages are processed
        $this->checkBlastCompletion($blast);

        Log::info("Message sent to {$this->recipient->phone_number} for blast {$blast->id}");
    }

    protected function markFailed(string $error): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        // Update blast count
        $blast = $this->recipient->blastSchedule;
        $blast->increment('failed_count');

        // Check if all messages are processed
        $this->checkBlastCompletion($blast);

        Log::warning("Message failed to {$this->recipient->phone_number} for blast {$blast->id}: {$error}");
    }

    protected function checkBlastCompletion($blast): void
    {
        $blast->refresh();

        $processed = $blast->sent_count + $blast->failed_count;

        if ($processed >= $blast->total_recipients) {
            $newStatus = $blast->failed_count > 0 && $blast->sent_count === 0 ? 'failed' : 'completed';
            $blast->update(['status' => $newStatus]);

            Log::info("Blast {$blast->id} completed with status: {$newStatus}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendWhatsappMessage job failed for recipient {$this->recipient->id}: " . $exception->getMessage());

        $this->markFailed($exception->getMessage());
    }
}
