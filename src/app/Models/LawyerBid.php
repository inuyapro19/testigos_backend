<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LawyerBid extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'case_id',
        'lawyer_id',
        'funding_goal_proposed',
        'expected_return_percentage',
        'lawyer_evaluation_fee',
        'lawyer_success_fee_percentage',
        'lawyer_fixed_fee',
        'success_probability',
        'estimated_duration_months',
        'legal_strategy',
        'experience_summary',
        'why_best_candidate',
        'similar_cases_won',
        'similar_cases_description',
        'attachments',
        'status',
        'admin_score',
        'admin_feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'funding_goal_proposed' => 'decimal:2',
        'expected_return_percentage' => 'decimal:2',
        'lawyer_evaluation_fee' => 'decimal:2',
        'lawyer_success_fee_percentage' => 'decimal:2',
        'lawyer_fixed_fee' => 'decimal:2',
        'success_probability' => 'decimal:2',
        'attachments' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the case that owns the bid.
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    /**
     * Get the lawyer that made the bid.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    /**
     * Get the admin that reviewed the bid.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include submitted bids.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope a query to only include bids for a specific case.
     */
    public function scopeForCase($query, $caseId)
    {
        return $query->where('case_id', $caseId);
    }

    /**
     * Scope a query to only include bids by a specific lawyer.
     */
    public function scopeByLawyer($query, $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Scope a query to only include accepted bids.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Check if the bid is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    /**
     * Check if the bid can be withdrawn.
     */
    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'under_review']);
    }

    /**
     * Check if the bid was accepted.
     */
    public function wasAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the bid was rejected.
     */
    public function wasRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark bid as under review.
     */
    public function markAsUnderReview(): void
    {
        $this->update(['status' => 'under_review']);
    }

    /**
     * Mark bid as accepted.
     */
    public function markAsAccepted(int $reviewedBy): void
    {
        $this->update([
            'status' => 'accepted',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Mark bid as rejected.
     */
    public function markAsRejected(int $reviewedBy, ?string $feedback = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'admin_feedback' => $feedback,
        ]);
    }

    /**
     * Mark bid as withdrawn.
     */
    public function markAsWithdrawn(): void
    {
        $this->update(['status' => 'withdrawn']);
    }

    /**
     * Calculate total lawyer compensation based on this bid's proposal.
     */
    public function calculateTotalCompensation(float $amountRecovered): float
    {
        $total = 0;

        // Add evaluation fee
        if ($this->lawyer_evaluation_fee) {
            $total += $this->lawyer_evaluation_fee;
        }

        // Add fixed fee
        if ($this->lawyer_fixed_fee) {
            $total += $this->lawyer_fixed_fee;
        }

        // Add success fee percentage
        if ($this->lawyer_success_fee_percentage && $amountRecovered > 0) {
            $successFee = ($amountRecovered * $this->lawyer_success_fee_percentage) / 100;
            $total += $successFee;
        }

        return $total;
    }
}
