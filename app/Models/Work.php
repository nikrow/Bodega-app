<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'name',
        'description',
        'crop_id',
        'created_by',
        'updated_by',
    ];
    protected static function booted()
    {
        static::creating(function ($work) {

            $work->created_by = Auth::id();
            $work->updated_by = Auth::id();
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
    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
}
