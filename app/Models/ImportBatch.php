<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'tenant',
        'import_date', 
        'total_records', 
        'success_count', 
        'failed_count'];

    public function events()
    {
        return $this->hasMany(ImportedEvent::class, 'batch_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'tenant', 'id');
    }
}