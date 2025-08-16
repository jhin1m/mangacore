<?php

namespace Ophim\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ReadingProgress extends Model
{
    protected $table = 'reading_progress';

    protected $fillable = [
        'user_id',
        'manga_id',
        'chapter_id',
        'page_number',
        'completed_at',
        'is_bookmarked',
        'bookmark_note'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'page_number' => 'integer',
        'is_bookmarked' => 'boolean'
    ];

    /**
     * Get the user that owns the reading progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manga associated with the reading progress.
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    /**
     * Get the chapter associated with the reading progress.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Update or create reading progress for a user/guest.
     *
     * @param int $mangaId
     * @param int $chapterId
     * @param int $pageNumber
     * @param int|null $userId
     * @return ReadingProgress|null
     */
    public static function updateProgress(int $mangaId, int $chapterId, int $pageNumber, ?int $userId = null): ?ReadingProgress
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        // For authenticated users, save to database
        if ($userId) {
            return static::updateOrCreate(
                [
                    'user_id' => $userId,
                    'manga_id' => $mangaId
                ],
                [
                    'chapter_id' => $chapterId,
                    'page_number' => $pageNumber,
                    'completed_at' => now()
                ]
            );
        }

        // For guest users, save to session
        static::saveGuestProgress($mangaId, $chapterId, $pageNumber);
        return null;
    }

    /**
     * Get reading progress for a user or guest.
     *
     * @param int $mangaId
     * @param int|null $userId
     * @return array|ReadingProgress|null
     */
    public static function getProgress(int $mangaId, ?int $userId = null)
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        // For authenticated users, get from database
        if ($userId) {
            return static::where('user_id', $userId)
                ->where('manga_id', $mangaId)
                ->with(['chapter', 'manga'])
                ->first();
        }

        // For guest users, get from session
        return static::getGuestProgress($mangaId);
    }

    /**
     * Save reading progress for guest users in session.
     *
     * @param int $mangaId
     * @param int $chapterId
     * @param int $pageNumber
     * @return void
     */
    protected static function saveGuestProgress(int $mangaId, int $chapterId, int $pageNumber): void
    {
        $sessionKey = 'reading_progress';
        $progress = Session::get($sessionKey, []);
        
        $progress[$mangaId] = [
            'manga_id' => $mangaId,
            'chapter_id' => $chapterId,
            'page_number' => $pageNumber,
            'completed_at' => now()->toISOString()
        ];
        
        Session::put($sessionKey, $progress);
    }

    /**
     * Get reading progress for guest users from session.
     *
     * @param int $mangaId
     * @return array|null
     */
    protected static function getGuestProgress(int $mangaId): ?array
    {
        $sessionKey = 'reading_progress';
        $progress = Session::get($sessionKey, []);
        
        return $progress[$mangaId] ?? null;
    }

    /**
     * Get all reading progress for current user or guest.
     *
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public static function getAllProgress(?int $userId = null)
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        // For authenticated users, get from database
        if ($userId) {
            return static::where('user_id', $userId)
                ->with(['manga', 'chapter'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // For guest users, get from session
        $sessionKey = 'reading_progress';
        return Session::get($sessionKey, []);
    }

    /**
     * Mark a chapter as completed.
     *
     * @param int $mangaId
     * @param int $chapterId
     * @param int|null $userId
     * @return ReadingProgress|null
     */
    public static function markChapterCompleted(int $mangaId, int $chapterId, ?int $userId = null): ?ReadingProgress
    {
        // Get the chapter to find the last page
        $chapter = Chapter::find($chapterId);
        if (!$chapter) {
            return null;
        }

        return static::updateProgress($mangaId, $chapterId, $chapter->page_count, $userId);
    }

    /**
     * Check if a chapter is completed for a user.
     *
     * @param int $mangaId
     * @param int $chapterId
     * @param int|null $userId
     * @return bool
     */
    public static function isChapterCompleted(int $mangaId, int $chapterId, ?int $userId = null): bool
    {
        $progress = static::getProgress($mangaId, $userId);
        
        if (!$progress) {
            return false;
        }

        // For database records
        if ($progress instanceof ReadingProgress) {
            $chapter = Chapter::find($chapterId);
            return $progress->chapter_id == $chapterId && 
                   $progress->page_number >= ($chapter->page_count ?? 1);
        }

        // For session data
        if (is_array($progress)) {
            $chapter = Chapter::find($chapterId);
            return $progress['chapter_id'] == $chapterId && 
                   $progress['page_number'] >= ($chapter->page_count ?? 1);
        }

        return false;
    }

    /**
     * Get reading progress percentage for a manga.
     *
     * @param int $mangaId
     * @param int|null $userId
     * @return float
     */
    public static function getProgressPercentage(int $mangaId, ?int $userId = null): float
    {
        $progress = static::getProgress($mangaId, $userId);
        
        if (!$progress) {
            return 0.0;
        }

        $manga = Manga::find($mangaId);
        if (!$manga || !$manga->total_chapters) {
            return 0.0;
        }

        $currentChapter = null;
        if ($progress instanceof ReadingProgress) {
            $currentChapter = $progress->chapter;
        } elseif (is_array($progress)) {
            $currentChapter = Chapter::find($progress['chapter_id']);
        }

        if (!$currentChapter) {
            return 0.0;
        }

        return ($currentChapter->chapter_number / $manga->total_chapters) * 100;
    }

    /**
     * Migrate guest progress to user account when user logs in.
     *
     * @param int $userId
     * @return int Number of progress records migrated
     */
    public static function migrateGuestProgress(int $userId): int
    {
        $sessionKey = 'reading_progress';
        $guestProgress = Session::get($sessionKey, []);
        $migratedCount = 0;

        foreach ($guestProgress as $mangaId => $progress) {
            // Only migrate if user doesn't already have progress for this manga
            $existingProgress = static::where('user_id', $userId)
                ->where('manga_id', $mangaId)
                ->first();

            if (!$existingProgress) {
                static::create([
                    'user_id' => $userId,
                    'manga_id' => $progress['manga_id'],
                    'chapter_id' => $progress['chapter_id'],
                    'page_number' => $progress['page_number'],
                    'completed_at' => $progress['completed_at']
                ]);
                $migratedCount++;
            }
        }

        // Clear guest progress from session after migration
        Session::forget($sessionKey);

        return $migratedCount;
    }

    /**
     * Clean up old reading progress records.
     *
     * @param int $daysOld
     * @return int Number of records deleted
     */
    public static function cleanupOldProgress(int $daysOld = 365): int
    {
        return static::where('updated_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Add or update bookmark for a manga chapter.
     *
     * @param int $mangaId
     * @param int $chapterId
     * @param int $pageNumber
     * @param string|null $note
     * @param int|null $userId
     * @return ReadingProgress|null
     */
    public static function addBookmark(int $mangaId, int $chapterId, int $pageNumber, ?string $note = null, ?int $userId = null): ?ReadingProgress
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        // Only authenticated users can create bookmarks
        if (!$userId) {
            return null;
        }

        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'manga_id' => $mangaId
            ],
            [
                'chapter_id' => $chapterId,
                'page_number' => $pageNumber,
                'is_bookmarked' => true,
                'bookmark_note' => $note,
                'completed_at' => now()
            ]
        );
    }

    /**
     * Remove bookmark from reading progress.
     *
     * @param int $mangaId
     * @param int|null $userId
     * @return bool
     */
    public static function removeBookmark(int $mangaId, ?int $userId = null): bool
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        if (!$userId) {
            return false;
        }

        $progress = static::where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->first();

        if ($progress) {
            $progress->update([
                'is_bookmarked' => false,
                'bookmark_note' => null
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get all bookmarks for a user.
     *
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserBookmarks(?int $userId = null)
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        if (!$userId) {
            return collect();
        }

        return static::where('user_id', $userId)
            ->where('is_bookmarked', true)
            ->with(['manga', 'chapter'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Check if a manga is bookmarked by user.
     *
     * @param int $mangaId
     * @param int|null $userId
     * @return bool
     */
    public static function isBookmarked(int $mangaId, ?int $userId = null): bool
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        if (!$userId) {
            return false;
        }

        return static::where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->where('is_bookmarked', true)
            ->exists();
    }

    /**
     * Get reading history for a user (all progress records).
     *
     * @param int|null $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getReadingHistory(?int $userId = null, int $limit = 50)
    {
        // If no user ID provided, use current authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        if (!$userId) {
            return collect();
        }

        return static::where('user_id', $userId)
            ->with(['manga', 'chapter'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }
}