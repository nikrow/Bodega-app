<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'field_id',
        'created_by',
        'updated_by',
        'code',
        'RUT',
    ];
    protected static function booted()
    {
        static::creating(function ($operator) {
            $operator->field_id = Filament::getTenant()->id;
            $operator->created_by = Auth::id();
            $operator->updated_by = Auth::id();
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function machinery()
    {
        return $this->hasMany(Machinery::class);
    }
}
