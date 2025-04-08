<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RoleType;
use Filament\Panel;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Edwink\FilamentUserActivity\Traits\UserActivityTrait;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable implements Auditable, HasTenants, FilamentUser, HasApiTokens
{

    use HasFactory, Notifiable;
    use \Laravel\Sanctum\HasApiTokens;
    use \OwenIt\Auditing\Auditable;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
        'last_activity_at',
        'active_minutes',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $dates = [
        'last_login_at',
        'last_activity_at',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_minutes' => 'integer',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'role' => RoleType::class,
        ];
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_user', 'user_id', 'field_id');
    }
    public function isAdmin(): bool
    {
        return $this->role === RoleType::ADMIN;
    }
    public function assignedTractor()
    {
        return $this->hasOne(Tractor::class, 'operator_id');
    }
    public function assignedMachineries()
    {
        return $this->belongsToMany(Machinery::class, 'user_machinery', 'user_id', 'machinery_id');
    }
    public function isOperator()
    {
        return $this->role === RoleType::OPERARIO;
    }
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return $this->fields()->get();
    }
    public function audit()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->fields()->whereKey($tenant)->exists();
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    /**
     * Relación muchos a muchos con Warehouse.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse')
            ->withTimestamps();
    }


}
