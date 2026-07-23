<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'alternate_phone',
        'designation',
        'avatar',
        'is_active',
        'last_login_at',
        'password',
    ];

    /**
     * Keep existing API and view consumers compatible while using the new columns.
     *
     * @var list<string>
     */
    protected $appends = ['name', 'phone'];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ]))) ?: $this->username;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->mobile;
    }

    public function canAccessAdministration(): bool
    {
        return $this->hasAnyRole(RoleName::administration());
    }

    public function seesUnrestrictedRecords(): bool
    {
        return $this->hasAnyRole(RoleName::unrestricted());
    }

    public function primaryRoleName(): ?string
    {
        return $this->roles->first()?->name;
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdQuotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'created_by');
    }

    public function leadAssignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class, 'assigned_to');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }
}
