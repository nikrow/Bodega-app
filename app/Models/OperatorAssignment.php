<?php  

namespace App\Models;  

use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;  

class OperatorAssignment extends Model 
{     
    protected $fillable = [
        'user_id',
    ];
    
    protected $table = 'operator_assignments';
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($assignment) {
            Log::info('Creando registro OperatorAssignment', ['user_id' => $assignment->user_id]);
        });
        
        static::created(function ($assignment) {
            Log::info('Registro OperatorAssignment creado exitosamente', ['id' => $assignment->id, 'user_id' => $assignment->user_id]);
        });
        
        static::updating(function ($assignment) {
            $user = $assignment->user;
            if ($user) {
                Log::info('Actualizando OperatorAssignment', [
                    'user_id' => $assignment->user_id,
                    'tractors' => $assignment->getAttribute('tractors'),
                    'machineries' => $assignment->getAttribute('machineries')
                ]);
                DB::transaction(function () use ($user, $assignment) {
                    
                    $tractors = $assignment->getAttribute('tractors') ?? $user->assignedTractors()->pluck('tractor_id')->toArray();
                    $machineries = $assignment->getAttribute('machineries') ?? $user->assignedMachineries()->pluck('machinery_id')->toArray();
                    
                    
                    $user->assignedTractors()->sync($tractors);
                    $user->assignedMachineries()->sync($machineries);
                });
            }
        });
        
        static::deleting(function ($assignment) {
            $user = $assignment->user;
            if ($user) {
                Log::info('Eliminando OperatorAssignment', ['user_id' => $assignment->user_id]);
                DB::transaction(function () use ($user) {
                    $user->assignedTractors()->detach();
                    $user->assignedMachineries()->detach();
                });
            }
        });
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}