<?php

namespace App\Models;

use Filament\Panel;
use App\Enums\RoleType;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Models\Audit;
use App\Models\OperatorAssignment;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Edwink\FilamentUserActivity\Traits\UserActivityTrait;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable implements Auditable, HasTenants, FilamentUser
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
        'is_active',
    ];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    
    protected static function boot()
{
    parent::boot();

    static::created(function ($user) {
        $roleValue = $user->getRoleValue();
        Log::info('Usuario creado', [
            'user_id' => $user->id,
            'role_value' => $roleValue
        ]);
        if ($roleValue === 'operario') {
            Log::info('Creando OperatorAssignment para operario', ['user_id' => $user->id]);
            DB::transaction(function () use ($user) {
                OperatorAssignment::updateOrCreate(
                    ['user_id' => $user->id]
                );
            });
        }
    });

    static::updated(function ($user) {
        $roleValue = $user->getRoleValue();
        $originalRoleValue = $user->getOriginalRoleValue();
        Log::info('Usuario actualizado', [
            'user_id' => $user->id,
            'role' => $user->role,
            'role_value' => $roleValue,
            'original_role' => $user->getOriginal('role'),
            'original_role_value' => $originalRoleValue
        ]);

        if ($roleValue === 'operario' && $originalRoleValue !== 'operario') {
            Log::info('Creando o actualizando OperatorAssignment para operario', ['user_id' => $user->id]);
            DB::transaction(function () use ($user) {
                OperatorAssignment::updateOrCreate(
                    ['user_id' => $user->id]
                );
            });
        } elseif ($originalRoleValue === 'operario' && $roleValue !== 'operario') {
            Log::info('Eliminando OperatorAssignment porque el rol cambió de operario', ['user_id' => $user->id]);
            DB::transaction(function () use ($user) {
                if ($assignment = $user->operatorAssignment) {
                    $user->assignedTractors()->detach();
                    $user->assignedMachineries()->detach();
                    $assignment->delete();
                }
            });
        }
    });
}
    public function getRoleValue()
    {
    if ($this->role instanceof RoleType) {
        return $this->role->value;
    }
    return $this->role;
    }
    
    public function getOriginalRoleValue()
    {
    $originalRole = $this->getOriginal('role');
    if ($originalRole instanceof RoleType) {
        return $originalRole->value;
    }
    return $originalRole;
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
        return $this->getRoleValue() === 'admin';
    }
    
    public function operatorAssignment()
    {
        return $this->hasOne(OperatorAssignment::class);
    }
    
    public function assignedTractors()
    {
        return $this->belongsToMany(Tractor::class, 'tractor_user', 'user_id', 'tractor_id');
    }
    
    public function assignedMachineries()
    {
        return $this->belongsToMany(Machinery::class, 'user_machinery', 'user_id', 'machinery_id');
    }

    public function isOperator(): bool
    {
        return $this->getRoleValue() === 'operario';
    }
    
    public function hasRole(string $role): bool
    {
        return $this->getRoleValue() === $role;
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