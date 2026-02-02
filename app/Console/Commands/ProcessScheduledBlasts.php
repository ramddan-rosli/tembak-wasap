<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBlastSchedule;
use App\Models\BlastSchedule;
use Illuminate\Console\Command;

class ProcessScheduledBlasts extends Command
{
    protected $signature = 'blasts:process';
    protected $description = 'Process scheduled blasts that are due';

    public function handle(): int
    {
        $blasts = BlastSchedule::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($blasts->isEmpty()) {
            $this->info('No scheduled blasts to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$blasts->count()} blast(s) to process.");

        foreach ($blasts as $blast) {
            $this->info("Processing blast: {$blast->name} (ID: {$blast->id})");

            // Check if device is connected
            if (!$blast->whatsappDevice || !$blast->whatsappDevice->isConnected()) {
                $this->warn("Skipping blast {$blast->id}: WhatsApp device not connected.");
                continue;
            }

            ProcessBlastSchedule::dispatch($blast);
            $this->info("Dispatched blast {$blast->id} for processing.");
        }

        return Command::SUCCESS;
    }
}
