<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Publisher;
use Illuminate\Auth\Access\HandlesAuthorization;

class PublisherPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem publisher');
    }

    public function view($user, Publisher $publisher)
    {
        return $user->hasPermissionTo('Xem publisher');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm publisher');
    }

    public function update($user, Publisher $publisher)
    {
        return $user->hasPermissionTo('Sửa publisher');
    }

    public function delete($user, Publisher $publisher)
    {
        return $user->hasPermissionTo('Xóa publisher');
    }

    public function restore($user, Publisher $publisher)
    {
        return false;
    }

    public function forceDelete($user, Publisher $publisher)
    {
        return false;
    }
}