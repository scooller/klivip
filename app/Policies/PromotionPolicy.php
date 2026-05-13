<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Promotion $promotion): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($promotion->isGlobal()) {
            return true;
        }

        return $promotion->site_id !== null && $user->belongsToSite($promotion->site_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Promotion $promotion): bool
    {
        if ($promotion->isGlobal()) {
            return false;
        }

        return $this->view($user, $promotion);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Promotion $promotion): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($promotion->isGlobal()) {
            return false;
        }

        return $promotion->site_id !== null && $user->belongsToSite($promotion->site_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Promotion $promotion): bool
    {
        return $this->delete($user, $promotion);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Promotion $promotion): bool
    {
        return false;
    }
}
