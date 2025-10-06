<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'case_id',
        'investor_id',
        'amount',
        'expected_return_percentage',
        'expected_return_amount',
        'status',
        'payment_data',
        'confirmed_at',
        'completed_at',
        'actual_return',
        'notes',
        'platform_commission_percentage',
        'platform_commission_amount',
        'success_commission_percentage',
        'success_commission_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'expected_return_percentage' => 'decimal:2',
        'expected_return_amount' => 'decimal:2',
        'actual_return' => 'decimal:2',
        'payment_data' => 'array',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'platform_commission_percentage' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
        'success_commission_percentage' => 'decimal:2',
        'success_commission_amount' => 'decimal:2',
    ];

    /**
     * Get the case that owns the investment.
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    /**
     * Get the investor that made the investment.
     */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    /**
     * Calculate expected total return.
     */
    public function getExpectedTotalReturnAttribute(): float
    {
        return $this->amount + $this->expected_return_amount;
    }

    /**
     * Calculate actual return percentage.
     */
    public function getActualReturnPercentageAttribute(): ?float
    {
        if (!$this->actual_return || $this->amount == 0) {
            return null;
        }
        
        return round((($this->actual_return - $this->amount) / $this->amount) * 100, 2);
    }

    /**
     * Check if investment is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['confirmed', 'active']);
    }

    /**
     * Check if investment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Scope a query to only include investments with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include confirmed investments.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'active', 'completed']);
    }

    /**
     * Scope a query to only include active investments.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'active']);
    }

    /**
     * Get the transactions for the investment.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'investment_id');
    }

    /**
     * Get the withdrawals for the investment.
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'investment_id');
    }

    /**
     * Calculate ROI (Return on Investment) percentage.
     */
    public function getROIPercentageAttribute(): ?float
    {
        if (!$this->actual_return || $this->amount == 0) {
            return null;
        }

        return round((($this->actual_return - $this->amount) / $this->amount) * 100, 2);
    }

    /**
     * Calculate net profit.
     */
    public function getNetProfitAttribute(): ?float
    {
        if (!$this->actual_return) {
            return null;
        }

        return $this->actual_return - $this->amount;
    }
}