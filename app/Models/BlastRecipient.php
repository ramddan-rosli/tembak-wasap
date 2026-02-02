<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'blast_schedule_id',
        'phone_number',
        'status',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the blast schedule that owns the recipient.
     */
    public function blastSchedule(): BelongsTo
    {
        return $this->belongsTo(BlastSchedule::class);
    }

    /**
     * Check if the message was sent.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if the message failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the message is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get the WhatsApp chat ID for this recipient.
     */
    public function getChatId(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
        return $phone . '@s.whatsapp.net';
    }
}
