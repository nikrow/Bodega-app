<?php

namespace App\Policies;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleType;
use App\Models\Irrigation;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;

class IrrigationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
        ]);
    }
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Irrigation $irrigation): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Irrigation $irrigation)
    {
        // Verificar si el usuario pertenece al mismo tenant
        if ($irrigation->field_id !== Filament::getTenant()->id) {
            return Response::deny('No tienes permiso para modificar este riego.');
        }

        // Verificar el rol del usuario
        if (!in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
        ])) {
            return Response::deny('No tienes el rol necesario para modificar riegos.');
        }

        // Verificar si han pasado más de 5 días y el usuario no es ADMIN
        if ($irrigation->date->diffInDays(Carbon::now()) > 5 && $user->role !== RoleType::ADMIN) {
            return Response::deny('No se puede modificar el riego después de 5 días de su creación.');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Irrigation $irrigation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Irrigation $irrigation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Irrigation $irrigation): bool
    {
        return false;
    }
    public function audit(User $user, Irrigation $irrigation): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
        ]);
    }
    
}
