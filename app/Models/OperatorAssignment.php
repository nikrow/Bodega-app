<?php  

namespace App\Models;  

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OperatorAssignment extends Model implements Auditable
{     
    use \OwenIt\Auditing\Auditable;
    use \Spatie\Activitylog\Traits\LogsActivity;
    
    protected $fillable = [
        'user_id',
        'tractor_id',
        'machinery_id',
        'created_by',
        'updated_by',
        'updated_at',
    ];  
    
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($assignment) {
            
            Log::info('Actualizando OperatorAssignment', [
                'user_id' => $assignment->user_id,
                'tractors' => $assignment->tractors()->pluck('tractors.id')->toArray(),
                'machineries' => $assignment->machineries()->pluck('machineries.id')->toArray(),
            ]);
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTractors(): BelongsToMany
    {
        return $this->belongsToMany(Tractor::class, 'tractor_user', 'user_id', 'tractor_id');
    }
    
    public function assignedMachineries(): BelongsToMany
    {
        return $this->belongsToMany(Machinery::class, 'user_machinery', 'user_id', 'machinery_id');
    }
    
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'operator_id', 'user_id');
    }
}