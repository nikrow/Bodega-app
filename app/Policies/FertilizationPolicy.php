<?php

namespace App\Policies;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleType;
use App\Models\Fertilization;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\Response;

class FertilizationPolicy
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
    public function view(User $user, Fertilization $fertilization): bool
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
    public function update(User $user, Fertilization $fertilization)
    {
            Log::info('Policy update llamado', [
            'user_id' => $user->id,
            'role' => $user->role,
            'tenant_id' => Filament::getTenant()->id,
            'field_id' => $fertilization->field_id,
            'days_diff' => $fertilization->date->diffInDays(Carbon::now())
        ]);
        if (!in_array($user->role, [RoleType::ADMIN, RoleType::AGRONOMO, RoleType::ASISTENTE])) {
            return Response::deny('No tienes el rol necesario para modificar riegos.');
        }
        if ($fertilization->date->diffInDays(Carbon::now()) > 5 && $user->role !== RoleType::ADMIN) {
            return Response::deny('No se puede modificar el riego después de 5 días de su creación.');
        }
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Fertilization $fertilization): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Fertilization $fertilization): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Fertilization $fertilization): bool
    {
        return false;
    }
    public function audit(User $user, Fertilization $fertilization): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
        ]);
    }
}
