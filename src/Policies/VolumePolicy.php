<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Volume;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolumePolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem volume');
    }

    public function view($user, Volume $volume)
    {
        return $user->hasPermissionTo('Xem volume');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm volume');
    }

    public function update($user, Volume $volume)
    {
        return $user->hasPermissionTo('Sửa volume');
    }

    public function delete($user, Volume $volume)
    {
        return $user->hasPermissionTo('Xóa volume');
    }

    public function restore($user, Volume $volume)
    {
        return false;
    }

    public function forceDelete($user, Volume $volume)
    {
        return false;
    }
}