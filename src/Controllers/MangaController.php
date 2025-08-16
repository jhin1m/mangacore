<?php

namespace Ophim\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\ReadingProgress;

class MangaController extends Controller
{
    /**
     * Display manga details with chapter list
     *
     * @param Manga $manga
     * @return View
     */
    public function show(Manga $manga): View
    {
        // Load relationships
        $manga->load([
            'authors',
            'artists', 
            'publishers',
            'categories',
            'tags',
            'origins',
            'volumes.chapters' => function ($query) {
                $query->published()->orderBy('chapter_number');
            }
        ]);

        // Get chapters with reading progress
        $chapters = $this->getChaptersWithProgress($manga);

        // Get chapters organized by volumes for better display
        $chaptersByVolume = $this->getChaptersByVolume($manga);

        // Get related manga recommendations
        $relatedManga = $this->getRelatedManga($manga);

        // Get user's reading progress
        $readingProgress = ReadingProgress::getProgress($manga->id);

        // Generate SEO tags
        $manga->generateSeoTags();

        return view('core.manga.show', compact(
            'manga',
            'chapters',
            'chaptersByVolume',
            'relatedManga',
            'readingProgress'
        ));
    }

    /**
     * Get paginated chapter listing for manga
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function chapters(Request $request, Manga $manga): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortOrder = $request->input('sort', 'desc'); // 'asc' or 'desc'

        $chapters = $manga->chapters()
            ->published()
            ->orderBy('chapter_number', $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Add reading progress indicators
        $chaptersWithProgress = $chapters->getCollection()->map(function ($chapter) use ($manga) {
            $isCompleted = ReadingProgress::isChapterCompleted($manga->id, $chapter->id);
            
            return [
                'id' => $chapter->id,
                'title' => $chapter->getFormattedTitle(),
                'chapter_number' => $chapter->chapter_number,
                'page_count' => $chapter->page_count,
                'published_at' => $chapter->published_at?->format('Y-m-d'),
                'url' => $chapter->getUrl(),
                'is_completed' => $isCompleted,
                'is_premium' => $chapter->is_premium,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $chaptersWithProgress,
            'pagination' => [
                'current_page' => $chapters->currentPage(),
                'last_page' => $chapters->lastPage(),
                'per_page' => $chapters->perPage(),
                'total' => $chapters->total(),
                'has_more' => $chapters->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get chapters with reading progress indicators
     *
     * @param Manga $manga
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getChaptersWithProgress(Manga $manga)
    {
        $cacheKey = "manga_chapters_with_progress_{$manga->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($manga) {
            $chapters = $manga->chapters()
                ->published()
                ->with('volume')
                ->orderBy('chapter_number', 'desc')
                ->get();

            return $chapters->map(function ($chapter) use ($manga) {
                $chapter->is_completed = ReadingProgress::isChapterCompleted($manga->id, $chapter->id);
                return $chapter;
            });
        });
    }

    /**
     * Get chapters organized by volumes
     *
     * @param Manga $manga
     * @return array
     */
    protected function getChaptersByVolume(Manga $manga)
    {
        $cacheKey = "manga_chapters_by_volume_{$manga->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($manga) {
            $volumes = $manga->volumes()
                ->published()
                ->with(['chapters' => function ($query) {
                    $query->published()->orderBy('chapter_number');
                }])
                ->orderBy('volume_number')
                ->get();

            // Get chapters without volumes (standalone chapters)
            $standaloneChapters = $manga->chapters()
                ->published()
                ->whereNull('volume_id')
                ->orderBy('chapter_number')
                ->get();

            $result = [];

            // Add volumes with their chapters
            foreach ($volumes as $volume) {
                if ($volume->chapters->count() > 0) {
                    $result[] = [
                        'type' => 'volume',
                        'volume' => $volume,
                        'chapters' => $volume->chapters->map(function ($chapter) use ($manga) {
                            $chapter->is_completed = ReadingProgress::isChapterCompleted($manga->id, $chapter->id);
                            return $chapter;
                        })
                    ];
                }
            }

            // Add standalone chapters if any
            if ($standaloneChapters->count() > 0) {
                $result[] = [
                    'type' => 'standalone',
                    'volume' => null,
                    'chapters' => $standaloneChapters->map(function ($chapter) use ($manga) {
                        $chapter->is_completed = ReadingProgress::isChapterCompleted($manga->id, $chapter->id);
                        return $chapter;
                    })
                ];
            }

            return $result;
        });
    }

    /**
     * Get related manga recommendations
     *
     * @param Manga $manga
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRelatedManga(Manga $manga)
    {
        $cacheKey = "related_manga_{$manga->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 30 * 60), function () use ($manga) {
            // Get manga with similar attributes
            $categoryIds = $manga->categories->pluck('id');
            $tagIds = $manga->tags->pluck('id');
            $originIds = $manga->origins->pluck('id');
            $authorIds = $manga->authors->pluck('id');
            $artistIds = $manga->artists->pluck('id');

            $relatedManga = Manga::where('id', '!=', $manga->id)
                ->where(function ($query) use ($categoryIds, $tagIds, $originIds, $authorIds, $artistIds, $manga) {
                    // Same authors (highest priority)
                    if ($authorIds->isNotEmpty()) {
                        $query->whereHas('authors', function ($q) use ($authorIds) {
                            $q->whereIn('authors.id', $authorIds);
                        });
                    }
                    
                    // Same artists
                    if ($artistIds->isNotEmpty()) {
                        $query->orWhereHas('artists', function ($q) use ($artistIds) {
                            $q->whereIn('artists.id', $artistIds);
                        });
                    }
                    
                    // Same categories
                    if ($categoryIds->isNotEmpty()) {
                        $query->orWhereHas('categories', function ($q) use ($categoryIds) {
                            $q->whereIn('categories.id', $categoryIds);
                        });
                    }
                    
                    // Same tags
                    if ($tagIds->isNotEmpty()) {
                        $query->orWhereHas('tags', function ($q) use ($tagIds) {
                            $q->whereIn('tags.id', $tagIds);
                        });
                    }
                    
                    // Same origins
                    if ($originIds->isNotEmpty()) {
                        $query->orWhereHas('origins', function ($q) use ($originIds) {
                            $q->whereIn('origins.id', $originIds);
                        });
                    }
                    
                    // Same type and demographic
                    $query->orWhere(function ($q) use ($manga) {
                        $q->where('type', $manga->type)
                          ->where('demographic', $manga->demographic);
                    });
                })
                ->withCount([
                    'authors' => function ($query) use ($authorIds) {
                        if ($authorIds->isNotEmpty()) {
                            $query->whereIn('authors.id', $authorIds);
                        }
                    },
                    'categories' => function ($query) use ($categoryIds) {
                        if ($categoryIds->isNotEmpty()) {
                            $query->whereIn('categories.id', $categoryIds);
                        }
                    },
                    'tags' => function ($query) use ($tagIds) {
                        if ($tagIds->isNotEmpty()) {
                            $query->whereIn('tags.id', $tagIds);
                        }
                    }
                ])
                ->orderByRaw('(authors_count * 3 + categories_count * 2 + tags_count) DESC')
                ->orderBy('view_count', 'desc')
                ->orderBy('rating', 'desc')
                ->limit(12)
                ->get();

            return $relatedManga;
        });
    }

    /**
     * Get reading statistics for manga
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function statistics(Request $request, Manga $manga): JsonResponse
    {
        $stats = Cache::remember("manga_stats_{$manga->id}", setting('site_cache_ttl', 60 * 60), function () use ($manga) {
            return [
                'total_chapters' => $manga->chapters()->published()->count(),
                'total_pages' => $manga->chapters()->published()->sum('page_count'),
                'total_readers' => ReadingProgress::where('manga_id', $manga->id)->distinct('user_id')->count(),
                'completion_rate' => $this->getCompletionRate($manga),
                'average_rating' => $manga->getRating(),
                'view_stats' => [
                    'total' => $manga->view_count,
                    'daily' => $manga->view_day,
                    'weekly' => $manga->view_week,
                    'monthly' => $manga->view_month,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Calculate completion rate for manga
     *
     * @param Manga $manga
     * @return float
     */
    protected function getCompletionRate(Manga $manga): float
    {
        $totalReaders = ReadingProgress::where('manga_id', $manga->id)->count();
        
        if ($totalReaders === 0) {
            return 0.0;
        }

        $lastChapter = $manga->chapters()->published()->orderBy('chapter_number', 'desc')->first();
        
        if (!$lastChapter) {
            return 0.0;
        }

        $completedReaders = ReadingProgress::where('manga_id', $manga->id)
            ->where('chapter_id', $lastChapter->id)
            ->count();

        return ($completedReaders / $totalReaders) * 100;
    }

    /**
     * Get user's reading history for manga
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function readingHistory(Request $request, Manga $manga): JsonResponse
    {
        $progress = ReadingProgress::getProgress($manga->id);
        
        if (!$progress) {
            return response()->json([
                'success' => true,
                'history' => null,
                'message' => 'No reading history found'
            ]);
        }

        $historyData = [];
        
        if ($progress instanceof ReadingProgress) {
            // Database record for authenticated user
            $historyData = [
                'current_chapter' => [
                    'id' => $progress->chapter->id,
                    'title' => $progress->chapter->getFormattedTitle(),
                    'chapter_number' => $progress->chapter->chapter_number,
                    'url' => $progress->chapter->getUrl(),
                ],
                'current_page' => $progress->page_number,
                'last_read' => $progress->updated_at->diffForHumans(),
                'progress_percentage' => ReadingProgress::getProgressPercentage($manga->id),
            ];
        } elseif (is_array($progress)) {
            // Session data for guest user
            $chapter = \Ophim\Core\Models\Chapter::find($progress['chapter_id']);
            if ($chapter) {
                $historyData = [
                    'current_chapter' => [
                        'id' => $chapter->id,
                        'title' => $chapter->getFormattedTitle(),
                        'chapter_number' => $chapter->chapter_number,
                        'url' => $chapter->getUrl(),
                    ],
                    'current_page' => $progress['page_number'],
                    'last_read' => \Carbon\Carbon::parse($progress['completed_at'])->diffForHumans(),
                    'progress_percentage' => ReadingProgress::getProgressPercentage($manga->id),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'history' => $historyData
        ]);
    }

    /**
     * Get chapters organized by volumes for API
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function chaptersByVolume(Request $request, Manga $manga): JsonResponse
    {
        $chaptersByVolume = $this->getChaptersByVolume($manga);
        
        $formattedData = collect($chaptersByVolume)->map(function ($group) {
            $chapters = $group['chapters']->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'title' => $chapter->getFormattedTitle(),
                    'chapter_number' => $chapter->chapter_number,
                    'page_count' => $chapter->page_count,
                    'published_at' => $chapter->published_at?->format('Y-m-d'),
                    'url' => $chapter->getUrl(),
                    'is_completed' => $chapter->is_completed ?? false,
                    'is_premium' => $chapter->is_premium,
                ];
            });

            return [
                'type' => $group['type'],
                'volume' => $group['volume'] ? [
                    'id' => $group['volume']->id,
                    'volume_number' => $group['volume']->volume_number,
                    'title' => $group['volume']->getFormattedTitle(),
                    'chapter_count' => $group['volume']->chapter_count,
                    'published_at' => $group['volume']->published_at?->format('Y-m-d'),
                ] : null,
                'chapters' => $chapters
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData
        ]);
    }

    /**
     * Get personalized manga recommendations based on reading history
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function recommendations(Request $request, Manga $manga): JsonResponse
    {
        $userId = auth()->id();
        $cacheKey = $userId ? "user_recommendations_{$userId}_{$manga->id}" : "guest_recommendations_{$manga->id}";
        
        $recommendations = Cache::remember($cacheKey, setting('site_cache_ttl', 60 * 60), function () use ($manga, $userId) {
            $baseQuery = Manga::where('id', '!=', $manga->id);
            
            if ($userId) {
                // Get user's reading history
                $readMangaIds = ReadingProgress::where('user_id', $userId)
                    ->pluck('manga_id')
                    ->unique();
                
                if ($readMangaIds->isNotEmpty()) {
                    // Get categories/tags from user's reading history
                    $preferredCategories = \DB::table('manga_category')
                        ->whereIn('manga_id', $readMangaIds)
                        ->pluck('category_id')
                        ->unique();
                    
                    $preferredTags = \DB::table('manga_tag')
                        ->whereIn('manga_id', $readMangaIds)
                        ->pluck('tag_id')
                        ->unique();
                    
                    // Exclude already read manga
                    $baseQuery->whereNotIn('id', $readMangaIds);
                    
                    // Prioritize preferred categories and tags
                    if ($preferredCategories->isNotEmpty() || $preferredTags->isNotEmpty()) {
                        $baseQuery->where(function ($query) use ($preferredCategories, $preferredTags) {
                            if ($preferredCategories->isNotEmpty()) {
                                $query->whereHas('categories', function ($q) use ($preferredCategories) {
                                    $q->whereIn('categories.id', $preferredCategories);
                                });
                            }
                            
                            if ($preferredTags->isNotEmpty()) {
                                $query->orWhereHas('tags', function ($q) use ($preferredTags) {
                                    $q->whereIn('tags.id', $preferredTags);
                                });
                            }
                        });
                    }
                }
            }
            
            // Get related manga as fallback
            $relatedManga = $this->getRelatedManga($manga);
            
            return $baseQuery
                ->orderBy('rating', 'desc')
                ->orderBy('view_count', 'desc')
                ->limit(8)
                ->get()
                ->merge($relatedManga->take(4))
                ->unique('id')
                ->take(12);
        });
        
        $formattedRecommendations = $recommendations->map(function ($recommendedManga) {
            return [
                'id' => $recommendedManga->id,
                'title' => $recommendedManga->title,
                'slug' => $recommendedManga->slug,
                'cover_image' => $recommendedManga->getCoverUrl(),
                'rating' => $recommendedManga->getRating(),
                'type' => $recommendedManga->getType(),
                'status' => $recommendedManga->getStatus(),
                'url' => $recommendedManga->getUrl(),
                'categories' => $recommendedManga->categories->pluck('name')->take(3),
            ];
        });
        
        return response()->json([
            'success' => true,
            'recommendations' => $formattedRecommendations
        ]);
    }

    /**
     * Toggle manga bookmark/favorite status
     *
     * @param Request $request
     * @param Manga $manga
     * @return JsonResponse
     */
    public function toggleBookmark(Request $request, Manga $manga): JsonResponse
    {
        // This would require a user_manga_bookmarks table
        // For now, we'll use session storage for guest users
        
        $bookmarks = session('manga_bookmarks', []);
        $mangaId = $manga->id;
        
        if (in_array($mangaId, $bookmarks)) {
            // Remove bookmark
            $bookmarks = array_diff($bookmarks, [$mangaId]);
            $isBookmarked = false;
            $message = 'Removed from bookmarks';
        } else {
            // Add bookmark
            $bookmarks[] = $mangaId;
            $isBookmarked = true;
            $message = 'Added to bookmarks';
        }
        
        session(['manga_bookmarks' => array_values($bookmarks)]);
        
        return response()->json([
            'success' => true,
            'is_bookmarked' => $isBookmarked,
            'message' => $message
        ]);
    }
}