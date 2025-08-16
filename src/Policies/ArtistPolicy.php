<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\Artist;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArtistPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem artist');
    }

    public function view($user, Artist $artist)
    {
        return $user->hasPermissionTo('Xem artist');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm artist');
    }

    public function update($user, Artist $artist)
    {
        return $user->hasPermissionTo('Sửa artist');
    }

    public function delete($user, Artist $artist)
    {
        return $user->hasPermissionTo('Xóa artist');
    }

    public function restore($user, Artist $artist)
    {
        return false;
    }

    public function forceDelete($user, Artist $artist)
    {
        return false;
    }
}