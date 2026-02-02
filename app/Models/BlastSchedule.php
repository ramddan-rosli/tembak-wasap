<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlastSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_device_id',
        'name',
        'message',
        'media_path',
        'media_type',
        'scheduled_at',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    /**
     * Get the user that owns the schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the WhatsApp device for this schedule.
     */
    public function whatsappDevice(): BelongsTo
    {
        return $this->belongsTo(WhatsappDevice::class);
    }

    /**
     * Get the recipients for this schedule.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(BlastRecipient::class);
    }

    /**
     * Get only pending recipients.
     */
    public function pendingRecipients(): HasMany
    {
        return $this->hasMany(BlastRecipient::class)->where('status', 'pending');
    }

    /**
     * Get only sent recipients.
     */
    public function sentRecipients(): HasMany
    {
        return $this->hasMany(BlastRecipient::class)->where('status', 'sent');
    }

    /**
     * Get only failed recipients.
     */
    public function failedRecipients(): HasMany
    {
        return $this->hasMany(BlastRecipient::class)->where('status', 'failed');
    }

    /**
     * Check if the schedule is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the schedule is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the schedule is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the schedule has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round((($this->sent_count + $this->failed_count) / $this->total_recipients) * 100, 1);
    }

    /**
     * Check if the schedule has media.
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_path);
    }

    /**
     * Get the media URL.
     */
    public function getMediaUrl(): ?string
    {
        if (!$this->hasMedia()) {
            return null;
        }

        return asset('storage/' . $this->media_path);
    }
}
