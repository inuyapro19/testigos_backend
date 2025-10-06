<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \Spatie\Permission\Traits\HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rut',
        'birth_date',
        'address',
        'phone',
        'role',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get the cases submitted by this victim.
     */
    public function victimCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'victim_id');
    }

    /**
     * Get the cases handled by this lawyer.
     */
    public function lawyerCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'lawyer_id');
    }

    /**
     * Get the investments made by this investor.
     */
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'investor_id');
    }

    /**
     * Get the case updates created by this user.
     */
    public function caseUpdates(): HasMany
    {
        return $this->hasMany(CaseUpdate::class);
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the lawyer profile for this user.
     */
    public function lawyerProfile(): HasOne
    {
        return $this->hasOne(LawyerProfile::class);
    }

    /**
     * Get the investor profile for this user.
     */
    public function investorProfile(): HasOne
    {
        return $this->hasOne(InvestorProfile::class);
    }

    /**
     * Check if user is a victim.
     */
    public function isVictim(): bool
    {
        return $this->hasRole('victim');
    }

    /**
     * Check if user is a lawyer.
     */
    public function isLawyer(): bool
    {
        return $this->hasRole('lawyer');
    }

    /**
     * Check if user is an investor.
     */
    public function isInvestor(): bool
    {
        return $this->hasRole('investor');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get the primary role of the user.
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Scope a query to only include users with a given Spatie role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the transactions for this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Get the withdrawals for this user.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'user_id');
    }
}