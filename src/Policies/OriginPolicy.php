<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Origin;
use Illuminate\Auth\Access\HandlesAuthorization;

class OriginPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem origin');
    }

    public function view($user, Origin $origin)
    {
        return $user->hasPermissionTo('Xem origin');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm origin');
    }

    public function update($user, Origin $origin)
    {
        return $user->hasPermissionTo('Sửa origin');
    }

    public function delete($user, Origin $origin)
    {
        return $user->hasPermissionTo('Xóa origin');
    }

    public function restore($user, Origin $origin)
    {
        return false;
    }

    public function forceDelete($user, Origin $origin)
    {
        return false;
    }
}