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

        // Get delay settings from blast (default 5-15 seconds)
        $delayMin = $this->blast->delay_min ?? 5;
        $delayMax = $this->blast->delay_max ?? 15;

        // Dispatch individual message jobs with random staggered delays
        $cumulativeDelay = 0;
        foreach ($recipients as $recipient) {
            SendWhatsappMessage::dispatch($recipient)
                ->delay(now()->addSeconds($cumulativeDelay));

            // Add random delay for next message
            $cumulativeDelay += rand($delayMin, $delayMax);
        }

        Log::info("Dispatched {$recipients->count()} message jobs for blast {$this->blast->id} with random delays ({$delayMin}-{$delayMax}s)");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Blast schedule {$this->blast->id} failed: " . $exception->getMessage());

        $this->blast->update(['status' => 'failed']);
    }
}
