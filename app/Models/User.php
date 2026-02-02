<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_owner',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
        ];
    }

    /**
     * Get the WhatsApp devices for the user.
     */
    public function whatsappDevices(): HasMany
    {
        return $this->hasMany(WhatsappDevice::class);
    }

    /**
     * Get the blast schedules for the user.
     */
    public function blastSchedules(): HasMany
    {
        return $this->hasMany(BlastSchedule::class);
    }

    /**
     * Get only connected WhatsApp devices for the user.
     */
    public function connectedDevices(): HasMany
    {
        return $this->hasMany(WhatsappDevice::class)->where('status', 'connected');
    }
}
