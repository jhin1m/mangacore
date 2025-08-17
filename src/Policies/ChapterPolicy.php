<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Ophim\Core\Models\Chapter;

class ChapterPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function viewAny($user)
    {
        return $user->hasPermissionTo('Browse chapter') || $user->hasPermissionTo('Browse manga');
    }

    /**
     * Determine whether the user can view the chapter.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function view($user, Chapter $chapter)
    {
        return $user->hasPermissionTo('Browse chapter') || $user->hasPermissionTo('Browse manga');
    }

    /**
     * Determine whether the user can create chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function create($user)
    {
        return $user->hasPermissionTo('Create chapter') || $user->hasPermissionTo('Create manga');
    }

    /**
     * Determine whether the user can update the chapter.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function update($user, Chapter $chapter)
    {
        return $user->hasPermissionTo('Update chapter') || $user->hasPermissionTo('Update manga');
    }

    /**
     * Determine whether the user can delete the chapter.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function delete($user, Chapter $chapter)
    {
        return $user->hasPermissionTo('Delete chapter') || $user->hasPermissionTo('Delete manga');
    }

    /**
     * Determine whether the user can restore the chapter.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function restore($user, Chapter $chapter)
    {
        return $user->hasPermissionTo('Update chapter') || $user->hasPermissionTo('Update manga');
    }

    /**
     * Determine whether the user can permanently delete the chapter.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function forceDelete($user, Chapter $chapter)
    {
        return $user->hasPermissionTo('Delete chapter') || $user->hasPermissionTo('Delete manga');
    }

    /**
     * Determine whether the user can browse chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function browse($user)
    {
        return $user->hasPermissionTo('Browse chapter') || $user->hasPermissionTo('Browse manga');
    }

    /**
     * Determine whether the user can read chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function read($user)
    {
        return $user->hasPermissionTo('Browse chapter') || $user->hasPermissionTo('Browse manga');
    }

    /**
     * Determine whether the user can edit chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function edit($user)
    {
        return $user->hasPermissionTo('Update chapter') || $user->hasPermissionTo('Update manga');
    }

    /**
     * Determine whether the user can add chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function add($user)
    {
        return $user->hasPermissionTo('Create chapter') || $user->hasPermissionTo('Create manga');
    }

    /**
     * Determine whether the user can bulk delete chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function bulkDelete($user)
    {
        return $user->hasPermissionTo('Delete chapter') || $user->hasPermissionTo('Delete manga');
    }

    /**
     * Determine whether the user can manage chapter pages.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function managePages($user, Chapter $chapter)
    {
        return $this->update($user, $chapter);
    }

    /**
     * Determine whether the user can upload chapter images.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function uploadImages($user)
    {
        return $user->hasPermissionTo('Create chapter') || $user->hasPermissionTo('Update chapter');
    }

    /**
     * Determine whether the user can optimize chapter images.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  \Ophim\Core\Models\Chapter  $chapter
     * @return mixed
     */
    public function optimizeImages($user, Chapter $chapter)
    {
        return $this->update($user, $chapter);
    }

    /**
     * Determine whether the user can schedule chapter publishing.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function schedulePublishing($user)
    {
        return $user->hasPermissionTo('Update chapter') || $user->hasPermissionTo('Update manga');
    }

    /**
     * Determine whether the user can batch upload chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function batchUpload($user)
    {
        return $user->hasPermissionTo('Create chapter') || $user->hasPermissionTo('Create manga');
    }
}