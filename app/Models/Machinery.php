<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    protected $fillable = [
        'name',
        'field_id',
        'provider',
        'SapCode',
        'price',
        'qrcode',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'price' => 'decimal:2',
    ];
    protected static function booted()
    {
        static::creating(function ($machinery) {
            $machinery->field_id = Filament::getTenant()->id;
            $machinery->created_by = Auth::id();
            $machinery->updated_by = Auth::id();
            $machinery->qrcode = uniqid();
        });

        static::updating(function ($machinery) {
            $machinery->updated_by = Auth::id();
        });
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
