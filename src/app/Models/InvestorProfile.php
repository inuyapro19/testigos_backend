<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'investor_type',
        'total_invested',
        'total_returns',
        'active_investments',
        'completed_investments',
        'average_return_rate',
        'investment_preferences',
        'minimum_investment',
        'maximum_investment',
        'is_accredited',
        'accredited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_invested' => 'decimal:2',
        'total_returns' => 'decimal:2',
        'average_return_rate' => 'decimal:2',
        'minimum_investment' => 'decimal:2',
        'maximum_investment' => 'decimal:2',
        'investment_preferences' => 'array',
        'is_accredited' => 'boolean',
        'accredited_at' => 'datetime',
    ];

    /**
     * Get the user that owns the investor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update investor statistics.
     */
    public function updateStatistics(): void
    {
        // Use query builder instead of collection to access scopes
        $this->total_invested = $this->user->investments()->confirmed()->sum('amount');
        $this->active_investments = $this->user->investments()->active()->count();
        $this->completed_investments = $this->user->investments()->where('status', 'completed')->count();

        $completedInvestmentsCount = $this->user->investments()->where('status', 'completed')->count();
        if ($completedInvestmentsCount > 0) {
            $this->total_returns = $this->user->investments()->where('status', 'completed')->sum('actual_return');

            // Calculate average return percentage manually
            $completedInvestments = $this->user->investments()->where('status', 'completed')->get();
            $totalReturnPercentage = 0;
            $validCount = 0;

            foreach ($completedInvestments as $investment) {
                if ($investment->actual_return_percentage !== null) {
                    $totalReturnPercentage += $investment->actual_return_percentage;
                    $validCount++;
                }
            }

            $this->average_return_rate = $validCount > 0 ? $totalReturnPercentage / $validCount : 0;
        }

        $this->save();
    }

    /**
     * Get net profit.
     */
    public function getNetProfitAttribute(): float
    {
        return $this->total_returns - $this->total_invested;
    }

    /**
     * Scope a query to only include accredited investors.
     */
    public function scopeAccredited($query)
    {
        return $query->where('is_accredited', true);
    }

    /**
     * Scope a query to filter by investor type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('investor_type', $type);
    }
}