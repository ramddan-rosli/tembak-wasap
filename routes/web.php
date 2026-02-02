<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Blasts\Create as BlastCreate;
use App\Livewire\Blasts\Index as BlastIndex;
use App\Livewire\Blasts\Show as BlastShow;
use App\Livewire\Dashboard;
use App\Livewire\WhatsappDevices\Index as WhatsappIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Register route - accessible by guests (first user) or authenticated owners
Route::get('/register', Register::class)->name('register');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // WhatsApp Devices
    Route::get('/whatsapp', WhatsappIndex::class)->name('whatsapp.index');

    // Blast Schedules
    Route::get('/blasts', BlastIndex::class)->name('blasts.index');
    Route::get('/blasts/create', BlastCreate::class)->name('blasts.create');
    Route::get('/blasts/{blast}', BlastShow::class)->name('blasts.show');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
