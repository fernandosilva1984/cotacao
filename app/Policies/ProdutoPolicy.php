<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProdutoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Ver produto');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Produto $produto): bool
    {
        return $user->hasPermissionTo('Ver produto');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar produto');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Produto $produto): bool
    {
        return $user->hasPermissionTo('Editar produto');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Produto $produto): bool
    {
        return $user->hasPermissionTo('Deletar produto');
    }

    
}