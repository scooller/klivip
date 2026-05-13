<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
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
    public function view(User $user, Site $site): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->belongsToSite($site);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Site $site): bool
    {
        return $user->isAdmin() && $user->belongsToSite($site);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Site $site): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Site $site): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Site $site): bool
    {
        return false;
    }
}
