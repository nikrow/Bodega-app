<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class Field extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'created_by',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }
    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->slug = Str::slug($field->name);
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function crop()
    {
        return $this->HasMany(Crop::class, 'crop_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function wharehouses()
    {
        return $this->hasMany(Wharehouse::class);
    }

    public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }
}

