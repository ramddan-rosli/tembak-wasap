<?php

namespace App\Livewire\Blasts;

use App\Jobs\SendWhatsappMessage;
use App\Models\BlastSchedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use WithPagination;

    public BlastSchedule $blast;
    public string $recipientFilter = '';

    public function mount(BlastSchedule $blast): void
    {
        // Ensure blast belongs to current user
        if ($blast->user_id !== Auth::id()) {
            abort(403);
        }

        $this->blast = $blast;
    }

    public function getTitle(): string
    {
        return $this->blast->name;
    }

    public function updatingRecipientFilter(): void
    {
        $this->resetPage();
    }

    public function retryFailed(): void
    {
        if ($this->blast->status !== 'completed' && $this->blast->status !== 'failed') {
            session()->flash('error', 'Cannot retry while blast is still processing.');
            return;
        }

        $failedRecipients = $this->blast->recipients()->where('status', 'failed')->get();

        if ($failedRecipients->isEmpty()) {
            session()->flash('error', 'No failed recipients to retry.');
            return;
        }

        // Reset failed recipients to pending
        $this->blast->recipients()->where('status', 'failed')->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        // Update blast status
        $this->blast->update([
            'status' => 'processing',
            'failed_count' => 0,
        ]);

        // Dispatch jobs for failed recipients
        foreach ($failedRecipients as $recipient) {
            SendWhatsappMessage::dispatch($recipient->fresh())->delay(now()->addSeconds(2));
        }

        session()->flash('success', 'Retrying ' . $failedRecipients->count() . ' failed messages.');
    }

    public function refreshBlast(): void
    {
        $this->blast->refresh();
    }

    public function render()
    {
        $recipients = $this->blast->recipients()
            ->when($this->recipientFilter, fn($q) => $q->where('status', $this->recipientFilter))
            ->orderBy('status')
            ->paginate(20);

        return view('livewire.blasts.show', [
            'recipients' => $recipients,
        ])->title($this->blast->name);
    }
}
