<?php

namespace Ophim\Core\Policies;

use Backpack\PermissionManager\app\Models\Role;
use Ophim\Core\Models\ReadingProgress;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReadingProgressPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasPermissionTo('Xem reading_progress');
    }

    public function view($user, ReadingProgress $readingProgress)
    {
        return $user->hasPermissionTo('Xem reading_progress') || $readingProgress->user_id === $user->id;
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Thêm reading_progress');
    }

    public function update($user, ReadingProgress $readingProgress)
    {
        return $user->hasPermissionTo('Sửa reading_progress') || $readingProgress->user_id === $user->id;
    }

    public function delete($user, ReadingProgress $readingProgress)
    {
        return $user->hasPermissionTo('Xóa reading_progress') || $readingProgress->user_id === $user->id;
    }

    public function restore($user, ReadingProgress $readingProgress)
    {
        return false;
    }

    public function forceDelete($user, ReadingProgress $readingProgress)
    {
        return false;
    }
}