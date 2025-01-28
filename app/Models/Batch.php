<?php

namespace App\Models;

use Illuminate\Support\Str;
use Filament\Facades\Filament;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'field_id',
        'product_id',
        'quantity',
        'expiration_date',
        'lot_number',
        'buy_order',
        'invoice_number',
        'provider',
    ];
    protected static function booted()
    {
        static::creating(function ($batch) {
            $batch->field_id = Filament::getTenant()->id;
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
