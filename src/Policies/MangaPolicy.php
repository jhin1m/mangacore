<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class MangaPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
    }

    public function browse($user)
    {
        return $user->hasPermissionTo('Browse manga');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Create manga');
    }

    public function update($user, $entry)
    {
        return $user->hasPermissionTo('Update manga');
    }

    public function delete($user, $entry)
    {
        return $user->hasPermissionTo('Delete manga');
    }

    public function bulkDelete($user)
    {
        return $user->hasPermissionTo('Delete manga');
    }
}