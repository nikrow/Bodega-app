<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_name',
        'active_ingredients',
        'SAP_code',
        'family',
        'price',
        'created_by',
        'updated_by',
        'category_id',
    ];
    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->updated_by = Auth::id();
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

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
