<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsappDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instance_id',
        'name',
        'phone_number',
        'status',
        'qr_code',
        'last_connected_at',
    ];

    protected function casts(): array
    {
        return [
            'last_connected_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and generate instance_id on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->instance_id)) {
                $device->instance_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blast schedules for this device.
     */
    public function blastSchedules(): HasMany
    {
        return $this->hasMany(BlastSchedule::class);
    }

    /**
     * Check if the device is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Check if the device is connecting.
     */
    public function isConnecting(): bool
    {
        return $this->status === 'connecting';
    }
}
