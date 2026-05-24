<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Banner $banner): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($banner->isGlobal()) {
            return true;
        }

        return $banner->sites()->whereIn('sites.id', $user->sites()->select('sites.id'))->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Banner $banner): bool
    {
        if ($banner->isGlobal()) {
            return false;
        }

        return $this->view($user, $banner);
    }

    public function delete(User $user, Banner $banner): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($banner->isGlobal()) {
            return false;
        }

        return $this->view($user, $banner);
    }

    public function restore(User $user, Banner $banner): bool
    {
        return $this->delete($user, $banner);
    }

    public function forceDelete(User $user, Banner $banner): bool
    {
        return false;
    }
}
