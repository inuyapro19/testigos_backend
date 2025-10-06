<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'withdrawal_id',
        'user_id',
        'investment_id',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'status',
        'payment_method',
        'payment_details',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'transaction_id',
        'transfer_reference',
        'processed_at',
        'completed_at',
        'user_notes',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_details' => 'array',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot function to generate withdrawal_id automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($withdrawal) {
            if (empty($withdrawal->withdrawal_id)) {
                $withdrawal->withdrawal_id = 'WTH-' . strtoupper(Str::random(12));
            }

            // Calculate net amount if not provided
            if (empty($withdrawal->net_amount)) {
                $withdrawal->net_amount = $withdrawal->amount - ($withdrawal->fee ?? 0);
            }
        });
    }

    /**
     * Get the user who requested the withdrawal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the investment associated with the withdrawal (if any).
     */
    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class, 'investment_id');
    }

    /**
     * Get the admin who approved the withdrawal.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the transaction associated with the withdrawal.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Scope a query to only include withdrawals with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending withdrawals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved withdrawals.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'processing', 'completed']);
    }

    /**
     * Scope a query to only include completed withdrawals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if withdrawal is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if withdrawal is approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'processing', 'completed']);
    }

    /**
     * Check if withdrawal is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if withdrawal can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if withdrawal can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Approve the withdrawal.
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the withdrawal.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark withdrawal as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark withdrawal as completed.
     */
    public function markAsCompleted(string $transferReference = null): void
    {
        $updateData = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($transferReference) {
            $updateData['transfer_reference'] = $transferReference;
        }

        $this->update($updateData);
    }

    /**
     * Cancel the withdrawal.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
