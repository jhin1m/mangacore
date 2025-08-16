<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Author;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthorPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem author');
    }

    public function view($user, Author $author)
    {
        return $user->hasPermissionTo('Xem author');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm author');
    }

    public function update($user, Author $author)
    {
        return $user->hasPermissionTo('Sửa author');
    }

    public function delete($user, Author $author)
    {
        return $user->hasPermissionTo('Xóa author');
    }

    public function restore($user, Author $author)
    {
        return false;
    }

    public function forceDelete($user, Author $author)
    {
        return false;
    }
}