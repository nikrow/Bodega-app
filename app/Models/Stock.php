<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Audit;
use OwenIt\Auditing\Contracts\Auditable;

class Stock extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'stock';
    protected $fillable = [
        'product_id',
        'quantity',
        'price',
        'total_price',
        'created_by',
        'updated_by',
        'field_id',
        'wharehouse_id',
    ];
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function wharehouse()
    {
        return $this->belongsTo(Wharehouse::class, 'wharehouse_id');
    }
}
