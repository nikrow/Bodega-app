<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;




use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class User extends Authenticatable implements Auditable, HasTenants
{

    use HasFactory, Notifiable;
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
        'role'
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_user', 'user_id', 'field_id');
    }
    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return $this->fields()->get();
    }
    public function audit()
    {
        return $this->morphMany(Audit::class, 'auditable_id', 'id');
    }
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->fields()->whereKey($tenant)->exists();
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

}
