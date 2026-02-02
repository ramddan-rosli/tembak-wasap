<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Register')]
class Register extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        // If user is logged in and not owner, redirect
        if (Auth::check() && !Auth::user()->is_owner) {
            $this->redirectRoute('dashboard');
        }
    }

    public function register(): void
    {
        $this->validate();

        // Check if this is the first user (make them owner)
        $isFirstUser = User::count() === 0;

        // If not first user and no authenticated owner, deny
        if (!$isFirstUser && (!Auth::check() || !Auth::user()->is_owner)) {
            abort(403, 'Only owners can register new users.');
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_owner' => $isFirstUser, // First user becomes owner
        ]);

        // If this was first user registration, log them in
        if ($isFirstUser) {
            Auth::login($user);
            session()->regenerate();
        }

        session()->flash('success', $isFirstUser ? 'Account created successfully!' : 'User registered successfully!');
        $this->redirectRoute('dashboard');
    }

    public function render()
    {
        $isFirstUser = User::count() === 0;

        return view('livewire.auth.register', [
            'isFirstUser' => $isFirstUser,
        ]);
    }
}
