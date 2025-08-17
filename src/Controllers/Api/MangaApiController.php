<?php

namespace Ophim\Core\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Origin;

class MangaApiController extends BaseApiController
{
    /**
     * Get paginated manga list with filtering and sorting (optimized)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Create cache key based on request parameters
            $cacheKey = 'manga_api_index:' . md5(serialize($request->all()));
            
            return Cache::remember($cacheKey, setting('api_cache_ttl', 5 * 60), function () use ($request) {
                // Optimize query with selective eager loading
                $query = Manga::with([
                    'authors:id,name,slug',
                    'artists:id,name,slug', 
                    'categories:id,name,slug',
                    'tags:id,name,slug',
                    'origins:id,name,slug'
                ]);

                // Apply filters with optimized queries
                $this->applyOptimizedFilters($query, $request);

                // Apply search with full-text search when possible
                $this->applyOptimizedSearch($query, $request);

                // Apply relationship filters with exists queries for better performance
                $this->applyRelationshipFilters($query, $request);

                // Apply sorting with index-optimized fields
                $sortParams = $this->getSortingParams($request, [
                    'title', 'publication_year', 'rating', 'view_count', 'created_at', 'updated_at'
                ]);
                $query->orderBy($sortParams['sort_by'], $sortParams['sort_order']);

                // Get pagination params with limits
                $paginationParams = $this->getPaginationParams($request, 50); // Max 50 per page
                
                // Paginate results
                $manga = $query->paginate($paginationParams['per_page']);

                // Transform data efficiently
                $transformedData = $manga->getCollection()->map(function ($item) {
                    return $this->transformMangaOptimized($item);
                });

                $manga->setCollection($transformedData);

                return $this->paginatedResponse($manga, 'Manga retrieved successfully');
            });

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Apply optimized filters to query
     */
    protected function applyOptimizedFilters($query, Request $request)
    {
        $filters = [
            'type' => 'type',
            'status' => 'status', 
            'demographic' => 'demographic',
            'reading_direction' => 'reading_direction',
            'publication_year' => 'publication_year',
            'is_completed' => 'is_completed',
            'is_recommended' => 'is_recommended',
            'is_adult_content' => 'is_adult_content'
        ];

        foreach ($filters as $param => $column) {
            if ($request->has($param)) {
                $value = $request->get($param);
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        // Rating range filter
        if ($request->has('rating_min')) {
            $query->where('rating', '>=', $request->get('rating_min'));
        }
        if ($request->has('rating_max')) {
            $query->where('rating', '<=', $request->get('rating_max'));
        }

        // View count filter
        if ($request->has('min_views')) {
            $query->where('view_count', '>=', $request->get('min_views'));
        }
    }

    /**
     * Apply optimized search to query
     */
    protected function applyOptimizedSearch($query, Request $request)
    {
        if ($request->has('search') && !empty($request->get('search'))) {
            $searchTerm = $request->get('search');
            
            // Use full-text search if available
            if (config('database.default') === 'mysql') {
                $query->whereRaw(
                    "MATCH(title, original_title, other_name, description) AGAINST(? IN BOOLEAN MODE)",
                    [$searchTerm . '*']
                );
            } else {
                // Fallback to LIKE search
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('original_title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('other_name', 'LIKE', "%{$searchTerm}%");
                });
            }
        }
    }

    /**
     * Apply relationship filters with exists queries
     */
    protected function applyRelationshipFilters($query, Request $request)
    {
        $relationshipFilters = [
            'category_id' => 'categories',
            'author_id' => 'authors',
            'artist_id' => 'artists',
            'publisher_id' => 'publishers',
            'origin_id' => 'origins',
            'tag_id' => 'tags'
        ];

        foreach ($relationshipFilters as $param => $relation) {
            if ($request->has($param)) {
                $ids = (array) $request->get($param);
                $query->whereHas($relation, function ($q) use ($ids) {
                    $q->whereIn('id', $ids);
                });
            }
        }
    }

    /**
     * Transform manga for API response (optimized)
     */
    private function transformMangaOptimized(Manga $manga): array
    {
        return [
            'id' => $manga->id,
            'title' => $manga->title,
            'slug' => $manga->slug,
            'original_title' => $manga->original_title,
            'cover_image' => $manga->getCoverUrl(),
            'type' => $manga->type,
            'status' => $manga->status,
            'demographic' => $manga->demographic,
            'publication_year' => $manga->publication_year,
            'rating' => $manga->rating,
            'view_count' => $manga->view_count,
            'is_completed' => $manga->is_completed,
            'is_recommended' => $manga->is_recommended,
            'url' => $manga->getUrl(),
            'authors' => $manga->authors->pluck('name'),
            'categories' => $manga->categories->pluck('name'),
            'updated_at' => optional($manga->updated_at)->toISOString()
        ];
    }

    /**
     * Get manga details
     */
    public function show(Manga $manga): JsonResponse
    {
        try {
            $manga->load([
                'authors', 'artists', 'publishers', 'categories', 'tags', 'origins',
                'chapters' => function ($query) {
                    $query->orderBy('chapter_number', 'asc');
                },
                'volumes' => function ($query) {
                    $query->orderBy('volume_number', 'asc');
                }
            ]);

            $data = $this->transformMangaDetailed($manga);

            return $this->successResponse($data, 'Manga details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve manga details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get manga chapters with pagination
     */
    public function chapters(Request $request, Manga $manga): JsonResponse
    {
        try {
            $query = $manga->chapters()->with(['volume']);

            // Apply sorting
            $sortParams = $this->getSortingParams($request, [
                'chapter_number', 'published_at', 'view_count', 'created_at'
            ]);
            $query->orderBy($sortParams['sort_by'], $sortParams['sort_order']);

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $chapters = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $chapters->getCollection()->map(function ($chapter) {
                return $this->transformChapter($chapter);
            });

            $chapters->setCollection($transformedData);

            return $this->paginatedResponse($chapters, 'Chapters retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve chapters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get manga volumes with chapters
     */
    public function volumes(Manga $manga): JsonResponse
    {
        try {
            $volumes = $manga->volumes()->with([
                'chapters' => function ($query) {
                    $query->orderBy('chapter_number', 'asc');
                }
            ])->orderBy('volume_number', 'asc')->get();

            $data = $volumes->map(function ($volume) {
                return [
                    'id' => $volume->id,
                    'volume_number' => $volume->volume_number,
                    'title' => $volume->title,
                    'published_at' => optional($volume->published_at)->toISOString(),
                    'chapter_count' => $volume->chapter_count,
                    'chapters' => $volume->chapters->map(function ($chapter) {
                        return $this->transformChapter($chapter);
                    })
                ];
            });

            return $this->successResponse($data, 'Volumes retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve volumes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get manga statistics
     */
    public function statistics(Manga $manga): JsonResponse
    {
        try {
            $stats = [
                'total_chapters' => $manga->chapters()->count(),
                'total_volumes' => $manga->volumes()->count(),
                'total_pages' => $manga->chapters()->withCount('pages')->get()->sum('pages_count'),
                'view_count' => $manga->view_count,
                'rating' => $manga->rating,
                'publication_year' => $manga->publication_year,
                'status' => $manga->status,
                'is_completed' => $manga->is_completed,
                'last_updated' => optional($manga->updated_at)->toISOString(),
                'latest_chapter' => optional($manga->chapters()->orderBy('chapter_number', 'desc')->first())->chapter_number
            ];

            return $this->successResponse($stats, 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get related manga recommendations
     */
    public function related(Manga $manga): JsonResponse
    {
        try {
            // Get related manga based on categories, authors, and tags
            $relatedQuery = Manga::where('id', '!=', $manga->id)
                ->where(function ($query) use ($manga) {
                    // Same categories
                    $query->whereHas('categories', function ($q) use ($manga) {
                        $q->whereIn('categories.id', $manga->categories->pluck('id'));
                    })
                    // Same authors
                    ->orWhereHas('authors', function ($q) use ($manga) {
                        $q->whereIn('authors.id', $manga->authors->pluck('id'));
                    })
                    // Same tags
                    ->orWhereHas('tags', function ($q) use ($manga) {
                        $q->whereIn('tags.id', $manga->tags->pluck('id'));
                    });
                })
                ->with(['authors', 'artists', 'categories'])
                ->orderBy('view_count', 'desc')
                ->limit(10);

            $related = $relatedQuery->get()->map(function ($item) {
                return $this->transformManga($item);
            });

            return $this->successResponse($related, 'Related manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve related manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search manga
     */
    public function search(Request $request, string $query): JsonResponse
    {
        try {
            $searchQuery = Manga::with(['authors', 'artists', 'categories'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('original_title', 'LIKE', "%{$query}%")
                      ->orWhere('other_name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->orderBy('view_count', 'desc');

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $results = $searchQuery->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $results->getCollection()->map(function ($item) {
                return $this->transformManga($item);
            });

            $results->setCollection($transformedData);

            return $this->paginatedResponse($results, 'Search results retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get tags
     */
    public function tags(): JsonResponse
    {
        try {
            $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($tags, 'Tags retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve tags: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get authors
     */
    public function authors(): JsonResponse
    {
        try {
            $authors = Author::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($authors, 'Authors retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve authors: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get artists
     */
    public function artists(): JsonResponse
    {
        try {
            $artists = Artist::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($artists, 'Artists retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve artists: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get publishers
     */
    public function publishers(): JsonResponse
    {
        try {
            $publishers = Publisher::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($publishers, 'Publishers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve publishers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get origins
     */
    public function origins(): JsonResponse
    {
        try {
            $origins = Origin::orderBy('name')->get(['id', 'name', 'slug']);
            return $this->successResponse($origins, 'Origins retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve origins: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform manga for API response
     */
    private function transformManga(Manga $manga): array
    {
        return [
            'id' => $manga->id,
            'title' => $manga->title,
            'slug' => $manga->slug,
            'original_title' => $manga->original_title,
            'other_name' => $manga->other_name,
            'description' => $manga->description,
            'cover_image' => $manga->getCoverUrl(),
            'banner_image' => $manga->getBannerUrl(),
            'type' => $manga->type,
            'status' => $manga->status,
            'demographic' => $manga->demographic,
            'reading_direction' => $manga->reading_direction,
            'publication_year' => $manga->publication_year,
            'total_chapters' => $manga->total_chapters,
            'total_volumes' => $manga->total_volumes,
            'rating' => $manga->rating,
            'view_count' => $manga->view_count,
            'is_completed' => $manga->is_completed,
            'is_recommended' => $manga->is_recommended,
            'is_adult_content' => $manga->is_adult_content,
            'url' => $manga->getUrl(),
            'authors' => $manga->authors->map(fn($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'slug' => $author->slug
            ]),
            'artists' => $manga->artists->map(fn($artist) => [
                'id' => $artist->id,
                'name' => $artist->name,
                'slug' => $artist->slug
            ]),
            'categories' => $manga->categories->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug
            ]),
            'tags' => $manga->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug
            ]),
            'origins' => $manga->origins->map(fn($origin) => [
                'id' => $origin->id,
                'name' => $origin->name,
                'slug' => $origin->slug
            ]),
            'created_at' => optional($manga->created_at)->toISOString(),
            'updated_at' => optional($manga->updated_at)->toISOString()
        ];
    }

    /**
     * Transform manga with detailed information
     */
    private function transformMangaDetailed(Manga $manga): array
    {
        $basic = $this->transformManga($manga);
        
        $basic['chapters'] = $manga->chapters->map(function ($chapter) {
            return $this->transformChapter($chapter);
        });

        $basic['volumes'] = $manga->volumes->map(function ($volume) {
            return [
                'id' => $volume->id,
                'volume_number' => $volume->volume_number,
                'title' => $volume->title,
                'published_at' => optional($volume->published_at)->toISOString(),
                'chapter_count' => $volume->chapter_count
            ];
        });

        return $basic;
    }

    /**
     * Get popular manga
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $query = Manga::with(['authors', 'artists', 'categories'])
                ->orderBy('view_count', 'desc');

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $manga = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $manga->getCollection()->map(function ($item) {
                return $this->transformManga($item);
            });

            $manga->setCollection($transformedData);

            return $this->paginatedResponse($manga, 'Popular manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve popular manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get latest manga
     */
    public function latest(Request $request): JsonResponse
    {
        try {
            $query = Manga::with(['authors', 'artists', 'categories'])
                ->orderBy('created_at', 'desc');

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $manga = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $manga->getCollection()->map(function ($item) {
                return $this->transformManga($item);
            });

            $manga->setCollection($transformedData);

            return $this->paginatedResponse($manga, 'Latest manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve latest manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get featured manga
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $query = Manga::with(['authors', 'artists', 'categories'])
                ->where('is_recommended', true)
                ->orderBy('updated_at', 'desc');

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $manga = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $manga->getCollection()->map(function ($item) {
                return $this->transformManga($item);
            });

            $manga->setCollection($transformedData);

            return $this->paginatedResponse($manga, 'Featured manga retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve featured manga: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform chapter for API response
     */
    private function transformChapter($chapter): array
    {
        return [
            'id' => $chapter->id,
            'title' => $chapter->title,
            'slug' => $chapter->slug,
            'chapter_number' => $chapter->chapter_number,
            'volume_number' => $chapter->volume_number,
            'page_count' => $chapter->page_count,
            'view_count' => $chapter->view_count,
            'published_at' => optional($chapter->published_at)->toISOString(),
            'is_premium' => $chapter->is_premium,
            'url' => $chapter->getUrl(),
            'created_at' => optional($chapter->created_at)->toISOString(),
            'updated_at' => optional($chapter->updated_at)->toISOString()
        ];
    }
}