<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'license_number',
        'law_firm',
        'specializations',
        'years_experience',
        'bio',
        'success_rate',
        'cases_handled',
        'total_recovered',
        'is_verified',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'specializations' => 'array',
        'success_rate' => 'decimal:2',
        'total_recovered' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the lawyer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update lawyer statistics.
     */
    public function updateStatistics(): void
    {
        $cases = $this->user->lawyerCases;

        $this->cases_handled = $cases->count();

        // Calculate success rate based on won cases
        $completedCases = $cases->where('status', 'completed');
        $wonCases = $completedCases->where('outcome', 'won');

        $this->success_rate = $completedCases->count() > 0
            ? round(($wonCases->count() / $completedCases->count()) * 100, 2)
            : 0;

        // Total recovered is the sum of amount_recovered from won cases
        $this->total_recovered = $wonCases->sum('amount_recovered');

        $this->save();
    }

    /**
     * Scope a query to only include verified lawyers.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to order by success rate.
     */
    public function scopeBySuccessRate($query, $direction = 'desc')
    {
        return $query->orderBy('success_rate', $direction);
    }
}