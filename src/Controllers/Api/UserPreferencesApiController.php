<?php

namespace Ophim\Core\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ophim\Core\Models\User;
use Ophim\Core\Models\Manga;

class UserPreferencesApiController extends BaseApiController
{
    /**
     * Get user preferences
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            $preferences = [
                'reading_mode' => $user->reading_mode,
                'image_quality' => $user->image_quality,
                'reading_preferences' => $user->reading_preferences ?? [],
                'statistics' => $user->getReadingStats()
            ];

            return $this->successResponse($preferences, 'User preferences retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user preferences: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user preferences
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reading_mode' => 'nullable|in:single,double,vertical,horizontal',
                'image_quality' => 'nullable|in:low,medium,high',
                'reading_preferences' => 'nullable|array',
                'reading_preferences.auto_bookmark' => 'nullable|boolean',
                'reading_preferences.preload_pages' => 'nullable|integer|min:1|max:10',
                'reading_preferences.auto_next_chapter' => 'nullable|boolean',
                'reading_preferences.reading_timer' => 'nullable|boolean',
                'reading_preferences.page_transition' => 'nullable|in:fade,slide,none'
            ]);

            $user = Auth::user();

            // Update basic preferences
            if (isset($validated['reading_mode'])) {
                $user->reading_mode = $validated['reading_mode'];
            }

            if (isset($validated['image_quality'])) {
                $user->image_quality = $validated['image_quality'];
            }

            // Update reading preferences
            if (isset($validated['reading_preferences'])) {
                $user->updateReadingPreferences($validated['reading_preferences']);
            }

            $user->save();

            $preferences = [
                'reading_mode' => $user->reading_mode,
                'image_quality' => $user->image_quality,
                'reading_preferences' => $user->reading_preferences ?? []
            ];

            return $this->successResponse($preferences, 'User preferences updated successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user preferences: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Rate a manga
     */
    public function rateManga(Request $request, Manga $manga): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rating' => 'required|numeric|min:1|max:10',
                'review' => 'nullable|string|max:1000'
            ]);

            $user = Auth::user();

            $user->rateManga(
                $manga->id,
                $validated['rating'],
                $validated['review'] ?? null
            );

            // Update manga's overall rating
            $manga->updateOverallRating();

            $data = [
                'manga_id' => $manga->id,
                'user_rating' => $validated['rating'],
                'user_review' => $validated['review'] ?? null,
                'manga_average_rating' => $manga->getAverageUserRating(),
                'manga_rating_count' => $manga->getUserRatingCount()
            ];

            return $this->successResponse($data, 'Manga rated successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to rate manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add manga to favorites
     */
    public function addToFavorites(Manga $manga): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->hasFavorited($manga->id)) {
                return $this->errorResponse('Manga is already in favorites', 400);
            }

            $user->addToFavorites($manga->id);

            $data = [
                'manga_id' => $manga->id,
                'is_favorite' => true,
                'total_favorites' => $manga->getFavoriteCount()
            ];

            return $this->successResponse($data, 'Manga added to favorites successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add manga to favorites: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove manga from favorites
     */
    public function removeFromFavorites(Manga $manga): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->hasFavorited($manga->id)) {
                return $this->errorResponse('Manga is not in favorites', 400);
            }

            $user->removeFromFavorites($manga->id);

            $data = [
                'manga_id' => $manga->id,
                'is_favorite' => false,
                'total_favorites' => $manga->getFavoriteCount()
            ];

            return $this->successResponse($data, 'Manga removed from favorites successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove manga from favorites: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's favorite manga
     */
    public function favorites(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $query = $user->favoriteMangas()->with(['authors', 'artists', 'categories']);

            // Apply sorting
            $sortParams = $this->getSortingParams($request, [
                'title', 'updated_at', 'rating', 'view_count'
            ]);
            
            if ($sortParams['sort_by'] === 'title') {
                $query->orderBy('mangas.title', $sortParams['sort_order']);
            } else {
                $query->orderBy('manga_user.updated_at', $sortParams['sort_order']);
            }

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $favorites = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $favorites->getCollection()->map(function ($manga) use ($user) {
                return $this->transformMangaForUser($manga, $user);
            });

            $favorites->setCollection($transformedData);

            return $this->paginatedResponse($favorites, 'Favorite manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve favorite manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's rated manga
     */
    public function ratings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $query = $user->ratedMangas()
                ->whereNotNull('manga_user.rating')
                ->with(['authors', 'artists', 'categories']);

            // Apply sorting
            $sortParams = $this->getSortingParams($request, [
                'rating', 'updated_at', 'title'
            ]);
            
            if ($sortParams['sort_by'] === 'rating') {
                $query->orderBy('manga_user.rating', $sortParams['sort_order']);
            } elseif ($sortParams['sort_by'] === 'title') {
                $query->orderBy('mangas.title', $sortParams['sort_order']);
            } else {
                $query->orderBy('manga_user.updated_at', $sortParams['sort_order']);
            }

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $ratings = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $ratings->getCollection()->map(function ($manga) use ($user) {
                $data = $this->transformMangaForUser($manga, $user);
                $data['user_rating'] = $manga->pivot->rating;
                $data['user_review'] = $manga->pivot->review;
                $data['rated_at'] = $manga->pivot->updated_at->toISOString();
                return $data;
            });

            $ratings->setCollection($transformedData);

            return $this->paginatedResponse($ratings, 'Rated manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve rated manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's reading statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $user->getReadingStats();

            return $this->successResponse($stats, 'Reading statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reading statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recently read manga
     */
    public function recentlyRead(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $limit = min($request->get('limit', 10), 50); // Max 50 items
            $recentlyRead = $user->getRecentlyRead($limit);

            $transformedData = $recentlyRead->map(function ($progress) {
                return [
                    'manga' => [
                        'id' => $progress->manga->id,
                        'title' => $progress->manga->title,
                        'slug' => $progress->manga->slug,
                        'cover_image' => $progress->manga->getCoverUrl(),
                        'url' => $progress->manga->getUrl(),
                        'type' => $progress->manga->type,
                        'status' => $progress->manga->status
                    ],
                    'chapter' => [
                        'id' => $progress->chapter->id,
                        'title' => $progress->chapter->title,
                        'chapter_number' => $progress->chapter->chapter_number,
                        'url' => $progress->chapter->getUrl()
                    ],
                    'progress' => [
                        'page_number' => $progress->page_number,
                        'total_pages' => $progress->chapter->page_count,
                        'percentage' => round(($progress->page_number / $progress->chapter->page_count) * 100, 2)
                    ],
                    'last_read' => $progress->updated_at->toISOString()
                ];
            });

            return $this->successResponse($transformedData, 'Recently read manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve recently read manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform manga data for user context
     */
    private function transformMangaForUser(Manga $manga, User $user): array
    {
        return [
            'id' => $manga->id,
            'title' => $manga->title,
            'slug' => $manga->slug,
            'original_title' => $manga->original_title,
            'description' => $manga->description,
            'cover_image' => $manga->getCoverUrl(),
            'banner_image' => $manga->getBannerUrl(),
            'type' => $manga->type,
            'status' => $manga->status,
            'demographic' => $manga->demographic,
            'rating' => $manga->rating,
            'rating_count' => $manga->rating_count,
            'view_count' => $manga->view_count,
            'total_chapters' => $manga->total_chapters,
            'url' => $manga->getUrl(),
            'authors' => $manga->authors->map(fn($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'url' => $author->getUrl()
            ]),
            'artists' => $manga->artists->map(fn($artist) => [
                'id' => $artist->id,
                'name' => $artist->name,
                'url' => $artist->getUrl()
            ]),
            'categories' => $manga->categories->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $category->getUrl()
            ]),
            'user_interaction' => [
                'is_favorite' => $user->hasFavorited($manga->id),
                'user_rating' => $user->getRatingFor($manga->id),
                'is_bookmarked' => \Ophim\Core\Models\ReadingProgress::isBookmarked($manga->id, $user->id)
            ]
        ];
    }
}