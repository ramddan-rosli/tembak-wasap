<?php

namespace App\Livewire\Blasts;

use App\Models\BlastSchedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Blast Schedules')]
class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteBlast(int $blastId): void
    {
        $blast = Auth::user()->blastSchedules()->findOrFail($blastId);

        // Only allow deleting pending blasts
        if ($blast->status !== 'pending') {
            session()->flash('error', 'Cannot delete a blast that is already processing or completed.');
            return;
        }

        $blast->recipients()->delete();
        $blast->delete();

        session()->flash('success', 'Blast schedule deleted successfully!');
    }

    public function render()
    {
        $blasts = Auth::user()->blastSchedules()
            ->with('whatsappDevice')
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.blasts.index', [
            'blasts' => $blasts,
        ]);
    }
}
