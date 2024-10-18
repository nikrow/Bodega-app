<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Aplicator extends Model
{
    use HasFactory;
    protected $table = 'aplicators';
    protected $fillable = [
        'field_id',
        'name',
        'rut',
        'tractor',
        'equipment',
        'created_by',
        'updated_by',
    ];
    protected static function booted()
    {
        static::creating(function ($aplicator) {
            $aplicator->created_by = Auth::id();
            $aplicator->updated_by = Auth::id();
        });
        static::updating(function ($aplicator) {
            $aplicator->updated_by = Auth::id();
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
        return $this->belongsTo(Field::class, 'field_id');
    }
}
