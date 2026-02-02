<?php

namespace App\Jobs;

use App\Models\BlastSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBlastSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour max

    public function __construct(
        public BlastSchedule $blast
    ) {}

    public function handle(): void
    {
        Log::info("Processing blast schedule: {$this->blast->id}");

        // Update status to processing
        $this->blast->update(['status' => 'processing']);

        // Get all pending recipients
        $recipients = $this->blast->pendingRecipients()->get();

        if ($recipients->isEmpty()) {
            $this->blast->update(['status' => 'completed']);
            Log::info("Blast {$this->blast->id} has no pending recipients.");
            return;
        }

        // Get message delay from config (default 2 seconds)
        $delay = config('whatsapp.message_delay', 2);

        // Dispatch individual message jobs with staggered delays
        foreach ($recipients as $index => $recipient) {
            SendWhatsappMessage::dispatch($recipient)
                ->delay(now()->addSeconds($index * $delay));
        }

        Log::info("Dispatched {$recipients->count()} message jobs for blast {$this->blast->id}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Blast schedule {$this->blast->id} failed: " . $exception->getMessage());

        $this->blast->update(['status' => 'failed']);
    }
}
