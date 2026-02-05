<?php

namespace App\Policies;

use App\Models\Marca;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MarcaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Ver marca');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Marca $marca): bool
    {
        return $user->hasPermissionTo('Ver marca');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar marca');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Marca $marca): bool
    {
        return $user->hasPermissionTo('Editar marca');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Marca $marca): bool
    {
        return $user->hasPermissionTo('Deletar marca');
    }

    
}