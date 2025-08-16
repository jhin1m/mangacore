<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Page;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagePolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem page');
    }

    public function view($user, Page $page)
    {
        return $user->hasPermissionTo('Xem page');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm page');
    }

    public function update($user, Page $page)
    {
        return $user->hasPermissionTo('Sửa page');
    }

    public function delete($user, Page $page)
    {
        return $user->hasPermissionTo('Xóa page');
    }

    public function restore($user, Page $page)
    {
        return false;
    }

    public function forceDelete($user, Page $page)
    {
        return false;
    }
}