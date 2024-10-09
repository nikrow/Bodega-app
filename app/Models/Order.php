<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $fillable = [
        'user_id',
        'status',
        'field_id',
        'created_at',
        'updated_at'
    ];

    protected static function booted()
    {
        static::creating(function ($order) {

            $order->user_id = Auth::id();
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con el usuario que actualizó el movimiento
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function orderLines()
    {
        return $this->hasMany(order_line::class);
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
