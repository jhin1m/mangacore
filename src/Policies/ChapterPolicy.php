<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Ophim\Core\Models\Chapter;

class ChapterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function viewAny($user)
    {
        return $user->can('browse chapters') || $user->can('browse manga');
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
        return $user->can('read chapters') || $user->can('read manga');
    }

    /**
     * Determine whether the user can create chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function create($user)
    {
        return $user->can('create chapters') || $user->can('create manga');
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
        return $user->can('update chapters') || $user->can('update manga');
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
        return $user->can('delete chapters') || $user->can('delete manga');
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
        return $user->can('restore chapters') || $user->can('restore manga');
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
        return $user->can('force delete chapters') || $user->can('force delete manga');
    }

    /**
     * Determine whether the user can browse chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function browse($user)
    {
        return $user->can('browse chapters') || $user->can('browse manga');
    }

    /**
     * Determine whether the user can read chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function read($user)
    {
        return $user->can('read chapters') || $user->can('read manga');
    }

    /**
     * Determine whether the user can edit chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function edit($user)
    {
        return $user->can('edit chapters') || $user->can('edit manga');
    }

    /**
     * Determine whether the user can add chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function add($user)
    {
        return $user->can('add chapters') || $user->can('add manga');
    }

    /**
     * Determine whether the user can bulk delete chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function bulkDelete($user)
    {
        return $user->can('bulk delete chapters') || $user->can('bulk delete manga');
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
        return $user->can('upload images') || $user->can('create chapters') || $user->can('update chapters');
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
        return $this->update($user, $chapter) && ($user->can('optimize images') || $user->can('update chapters'));
    }

    /**
     * Determine whether the user can schedule chapter publishing.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function schedulePublishing($user)
    {
        return $user->can('schedule publishing') || $user->can('update chapters') || $user->can('publish chapters');
    }

    /**
     * Determine whether the user can batch upload chapters.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return mixed
     */
    public function batchUpload($user)
    {
        return $user->can('batch upload') || $user->can('create chapters');
    }
}