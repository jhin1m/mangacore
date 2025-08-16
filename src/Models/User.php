<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use CrudTrait;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'reading_mode',
        'image_quality',
        'reading_preferences'
    ];

    protected $casts = [
        'reading_preferences' => 'array',
        'email_verified_at' => 'datetime'
    ];

    // Reading mode constants
    const READING_MODES = ['single', 'double', 'vertical', 'horizontal'];
    const IMAGE_QUALITIES = ['low', 'medium', 'high'];

    /**
     * Get the reading progress records for the user.
     */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /**
     * Get the manga that the user has rated/favorited.
     */
    public function ratedMangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'manga_user')
            ->withPivot(['rating', 'review', 'is_favorite'])
            ->withTimestamps();
    }

    /**
     * Get user's favorite manga.
     */
    public function favoriteMangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'manga_user')
            ->wherePivot('is_favorite', true)
            ->withPivot(['rating', 'review', 'is_favorite'])
            ->withTimestamps();
    }

    /**
     * Get total number of manga read by user.
     *
     * @return int
     */
    public function getTotalReadAttribute(): int
    {
        return $this->readingProgress()->distinct('manga_id')->count();
    }

    /**
     * Get user's favorite genres based on reading history.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getFavoriteGenres(int $limit = 5)
    {
        return $this->readingProgress()
            ->join('mangas', 'reading_progress.manga_id', '=', 'mangas.id')
            ->join('category_manga', 'mangas.id', '=', 'category_manga.manga_id')
            ->join('categories', 'category_manga.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.id, COUNT(*) as read_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('read_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get reading statistics for the user.
     *
     * @return array
     */
    public function getReadingStats(): array
    {
        $totalRead = $this->getTotalReadAttribute();
        $totalBookmarks = $this->readingProgress()->where('is_bookmarked', true)->count();
        $totalFavorites = $this->favoriteMangas()->count();
        $averageRating = $this->ratedMangas()->avg('manga_user.rating');
        
        // Get reading activity for last 30 days
        $recentActivity = $this->readingProgress()
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        // Get most read demographic
        $topDemographic = $this->readingProgress()
            ->join('mangas', 'reading_progress.manga_id', '=', 'mangas.id')
            ->selectRaw('mangas.demographic, COUNT(*) as read_count')
            ->groupBy('mangas.demographic')
            ->orderByDesc('read_count')
            ->first();

        return [
            'total_read' => $totalRead,
            'total_bookmarks' => $totalBookmarks,
            'total_favorites' => $totalFavorites,
            'average_rating' => $averageRating ? round($averageRating, 1) : null,
            'recent_activity' => $recentActivity,
            'top_demographic' => $topDemographic->demographic ?? null,
            'favorite_genres' => $this->getFavoriteGenres()
        ];
    }

    /**
     * Rate a manga.
     *
     * @param int $mangaId
     * @param float $rating
     * @param string|null $review
     * @return void
     */
    public function rateManga(int $mangaId, float $rating, ?string $review = null): void
    {
        $this->ratedMangas()->syncWithoutDetaching([
            $mangaId => [
                'rating' => $rating,
                'review' => $review,
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Add manga to favorites.
     *
     * @param int $mangaId
     * @return void
     */
    public function addToFavorites(int $mangaId): void
    {
        $this->ratedMangas()->syncWithoutDetaching([
            $mangaId => [
                'is_favorite' => true,
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Remove manga from favorites.
     *
     * @param int $mangaId
     * @return void
     */
    public function removeFromFavorites(int $mangaId): void
    {
        $this->ratedMangas()->updateExistingPivot($mangaId, [
            'is_favorite' => false,
            'updated_at' => now()
        ]);
    }

    /**
     * Check if user has favorited a manga.
     *
     * @param int $mangaId
     * @return bool
     */
    public function hasFavorited(int $mangaId): bool
    {
        return $this->favoriteMangas()->where('manga_id', $mangaId)->exists();
    }

    /**
     * Get user's rating for a manga.
     *
     * @param int $mangaId
     * @return float|null
     */
    public function getRatingFor(int $mangaId): ?float
    {
        $pivot = $this->ratedMangas()->where('manga_id', $mangaId)->first();
        return $pivot ? $pivot->pivot->rating : null;
    }

    /**
     * Update user reading preferences.
     *
     * @param array $preferences
     * @return void
     */
    public function updateReadingPreferences(array $preferences): void
    {
        $currentPreferences = $this->reading_preferences ?? [];
        $this->update([
            'reading_preferences' => array_merge($currentPreferences, $preferences)
        ]);
    }

    /**
     * Get a specific reading preference.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getReadingPreference(string $key, $default = null)
    {
        $preferences = $this->reading_preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    /**
     * Get recently read manga.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentlyRead(int $limit = 10)
    {
        return $this->readingProgress()
            ->with(['manga', 'chapter'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get continue reading list (manga with progress but not completed).
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getContinueReading(int $limit = 10)
    {
        return $this->readingProgress()
            ->with(['manga', 'chapter'])
            ->whereHas('manga', function ($query) {
                $query->where('is_completed', false);
            })
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
