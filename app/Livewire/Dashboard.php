<?php

namespace App\Livewire;

use App\Models\BlastSchedule;
use App\Models\WhatsappDevice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        $connectedDevices = $user->whatsappDevices()->where('status', 'connected')->count();
        $totalDevices = $user->whatsappDevices()->count();
        $pendingBlasts = $user->blastSchedules()->where('status', 'pending')->count();
        $processingBlasts = $user->blastSchedules()->where('status', 'processing')->count();

        $sentToday = $user->blastSchedules()
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->sum('sent_count');

        $recentBlasts = $user->blastSchedules()
            ->with('whatsappDevice')
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.dashboard', [
            'connectedDevices' => $connectedDevices,
            'totalDevices' => $totalDevices,
            'pendingBlasts' => $pendingBlasts,
            'processingBlasts' => $processingBlasts,
            'sentToday' => $sentToday,
            'recentBlasts' => $recentBlasts,
        ]);
    }
}
