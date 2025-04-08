<?php

namespace App\Models;

use App\Enums\CostType;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cost_type',
        'created_by',
    ];
    protected $casts = [
        'cost_type' => CostType::class,
    ];
    protected static function booted()
    {
        static::creating(function ($work) {

            $work->created_by = Auth::id();
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
    public function machineries()
    {
        return $this->belongsToMany(Machinery::class, 'machinery_work', 'work_id', 'machinery_id');
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
