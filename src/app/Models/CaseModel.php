<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'victim_id',
        'lawyer_id',
        'status',
        'category',
        'company',
        'funding_goal',
        'current_funding',
        'success_rate',
        'expected_return',
        'deadline',
        'legal_analysis',
        'evaluation_data',
        'lawyer_evaluation_fee',
        'lawyer_success_fee_percentage',
        'lawyer_fixed_fee',
        'lawyer_total_compensation',
        'lawyer_paid_at',
        'outcome',
        'amount_recovered',
        'legal_costs',
        'outcome_description',
        'resolution_date',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'funding_goal' => 'decimal:2',
        'current_funding' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'expected_return' => 'decimal:2',
        'deadline' => 'date',
        'evaluation_data' => 'array',
        'lawyer_evaluation_fee' => 'decimal:2',
        'lawyer_success_fee_percentage' => 'decimal:2',
        'lawyer_fixed_fee' => 'decimal:2',
        'lawyer_total_compensation' => 'decimal:2',
        'lawyer_paid_at' => 'datetime',
        'amount_recovered' => 'decimal:2',
        'legal_costs' => 'decimal:2',
        'resolution_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the victim that owns the case.
     */
    public function victim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'victim_id');
    }

    /**
     * Get the lawyer assigned to the case.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    /**
     * Get the documents for the case.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    /**
     * Get the investments for the case.
     */
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'case_id');
    }

    /**
     * Get the updates for the case.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CaseUpdate::class, 'case_id');
    }

    /**
     * Calculate funding percentage.
     */
    public function getFundingPercentageAttribute(): float
    {
        if (!$this->funding_goal || $this->funding_goal == 0) {
            return 0;
        }
        
        return round(($this->current_funding / $this->funding_goal) * 100, 2);
    }

    /**
     * Check if case is fully funded.
     */
    public function isFullyFunded(): bool
    {
        return $this->current_funding >= $this->funding_goal;
    }

    /**
     * Get remaining funding needed.
     */
    public function getRemainingFundingAttribute(): float
    {
        return max(0, $this->funding_goal - $this->current_funding);
    }

    /**
     * Scope a query to only include cases with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include published cases.
     */
    public function scopePublished($query)
    {
        return $query->whereIn('status', ['published', 'funded']);
    }

    /**
     * Scope a query to only include cases by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include cases needing funding.
     */
    public function scopeNeedsFunding($query)
    {
        return $query->whereIn('status', ['published', 'funded'])
                    ->whereColumn('current_funding', '<', 'funding_goal');
    }

    /**
     * Get the transactions for the case.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'case_id');
    }

    /**
     * Check if case is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if case is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if case can be closed.
     */
    public function canBeClosed(): bool
    {
        return in_array($this->status, ['funded', 'in_progress']);
    }

    /**
     * Calculate total lawyer compensation based on outcome.
     */
    public function calculateLawyerCompensation(): float
    {
        $total = 0;

        // Add evaluation fee
        if ($this->lawyer_evaluation_fee) {
            $total += $this->lawyer_evaluation_fee;
        }

        // Add fixed fee if case won
        if ($this->outcome === 'won' && $this->lawyer_fixed_fee) {
            $total += $this->lawyer_fixed_fee;
        }

        // Add success fee percentage if case won and amount recovered
        if ($this->outcome === 'won' && $this->amount_recovered && $this->lawyer_success_fee_percentage) {
            $successFee = ($this->amount_recovered * $this->lawyer_success_fee_percentage) / 100;
            $total += $successFee;
        }

        return $total;
    }

    /**
     * Check if case was won.
     */
    public function wasWon(): bool
    {
        return $this->outcome === 'won';
    }

    /**
     * Get net amount available for distribution (after legal costs and lawyer fees).
     */
    public function getNetAmountForDistributionAttribute(): float
    {
        if (!$this->amount_recovered) {
            return 0;
        }

        $netAmount = $this->amount_recovered;

        // Subtract legal costs
        if ($this->legal_costs) {
            $netAmount -= $this->legal_costs;
        }

        // Subtract lawyer compensation
        if ($this->lawyer_total_compensation) {
            $netAmount -= $this->lawyer_total_compensation;
        }

        return max(0, $netAmount);
    }
}