<?php

namespace Ophim\Core\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;

class ChapterApiController extends BaseApiController
{
    /**
     * Get chapter details with pages
     */
    public function show(Chapter $chapter): JsonResponse
    {
        try {
            $chapter->load(['manga', 'pages' => function ($query) {
                $query->orderBy('page_number', 'asc');
            }, 'volume']);

            $data = $this->transformChapterDetailed($chapter);

            return $this->successResponse($data, 'Chapter details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve chapter details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get chapter pages with optimization and caching
     */
    public function pages(Request $request, Chapter $chapter): JsonResponse
    {
        try {
            $quality = $request->get('quality', 'medium'); // low, medium, high
            $preload = min($request->get('preload', 3), 10); // Max 10 pages preload
            $currentPage = max($request->get('current_page', 1), 1);

            // Create cache key based on chapter and quality
            $cacheKey = "chapter_pages_api:{$chapter->id}:quality_{$quality}:preload_{$preload}";
            
            $data = Cache::remember($cacheKey, setting('api_cache_ttl', 10 * 60), function () use ($chapter, $quality, $preload, $currentPage) {
                // Use cached pages method from model
                $pages = $chapter->getCachedPages($quality);

                // Add preload information
                $preloadPages = $pages->slice($currentPage - 1, $preload + 1);

                return [
                    'chapter_id' => $chapter->id,
                    'total_pages' => $pages->count(),
                    'pages' => $pages,
                    'preload_pages' => $preloadPages->values(),
                    'reading_settings' => [
                        'reading_direction' => $chapter->manga->reading_direction,
                        'supports_webp' => true, // Assume modern clients
                        'cache_ttl' => 86400 // 24 hours
                    ]
                ];
            });

            // Add dynamic current page info
            $data['current_page'] = $currentPage;
            $data['reading_settings']['recommended_quality'] = $this->getRecommendedQuality($request);
            $data['reading_settings']['supports_webp'] = $this->supportsWebP($request);

            return $this->successResponse($data, 'Chapter pages retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve chapter pages: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get chapter navigation data (cached)
     */
    public function navigation(Chapter $chapter): JsonResponse
    {
        try {
            $cacheKey = "chapter_navigation_api:{$chapter->id}";
            
            $data = Cache::remember($cacheKey, setting('api_cache_ttl', 15 * 60), function () use ($chapter) {
                // Use cached navigation method from model
                $navigation = $chapter->getCachedNavigation();
                $manga = $chapter->manga;
                
                $chapterList = $navigation['chapter_list'];
                $currentPosition = $chapterList->search(function ($ch) use ($chapter) {
                    return $ch['id'] === $chapter->id;
                });

                return [
                    'current_chapter' => [
                        'id' => $chapter->id,
                        'title' => $chapter->title,
                        'chapter_number' => $chapter->chapter_number,
                        'volume_number' => $chapter->volume_number,
                        'page_count' => $chapter->page_count
                    ],
                    'previous_chapter' => $navigation['previous_chapter'] ? [
                        'id' => $navigation['previous_chapter']->id,
                        'title' => $navigation['previous_chapter']->title,
                        'chapter_number' => $navigation['previous_chapter']->chapter_number,
                        'url' => $navigation['previous_chapter']->getUrl()
                    ] : null,
                    'next_chapter' => $navigation['next_chapter'] ? [
                        'id' => $navigation['next_chapter']->id,
                        'title' => $navigation['next_chapter']->title,
                        'chapter_number' => $navigation['next_chapter']->chapter_number,
                        'url' => $navigation['next_chapter']->getUrl()
                    ] : null,
                    'manga' => [
                        'id' => $manga->id,
                        'title' => $manga->title,
                        'slug' => $manga->slug,
                        'url' => $manga->getUrl(),
                        'reading_direction' => $manga->reading_direction
                    ],
                    'chapter_list' => $chapterList,
                    'navigation_stats' => [
                        'current_position' => $currentPosition + 1,
                        'total_chapters' => $chapterList->count(),
                        'progress_percentage' => round((($currentPosition + 1) / $chapterList->count()) * 100, 2)
                    ]
                ];
            });

            return $this->successResponse($data, 'Navigation data retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve navigation data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Preload chapter data for smooth reading
     */
    public function preload(Request $request, Chapter $chapter): JsonResponse
    {
        try {
            $quality = $request->get('quality', 'medium');
            $preloadNext = $request->get('preload_next', true);
            
            $data = [
                'chapter_id' => $chapter->id,
                'preloaded_at' => now()->toISOString()
            ];

            // Preload current chapter pages
            $pages = $chapter->pages()
                ->orderBy('page_number', 'asc')
                ->limit(5) // Preload first 5 pages
                ->get()
                ->map(function ($page) use ($quality) {
                    return [
                        'page_number' => $page->page_number,
                        'image_url' => $page->getOptimizedUrl($quality),
                        'webp_url' => $page->getWebPUrl($quality)
                    ];
                });

            $data['preloaded_pages'] = $pages;

            // Preload next chapter if requested
            if ($preloadNext) {
                $nextChapter = $chapter->manga->chapters()
                    ->where('chapter_number', '>', $chapter->chapter_number)
                    ->orderBy('chapter_number', 'asc')
                    ->first();

                if ($nextChapter) {
                    $nextPages = $nextChapter->pages()
                        ->orderBy('page_number', 'asc')
                        ->limit(3) // Preload first 3 pages of next chapter
                        ->get()
                        ->map(function ($page) use ($quality) {
                            return [
                                'page_number' => $page->page_number,
                                'image_url' => $page->getOptimizedUrl($quality),
                                'webp_url' => $page->getWebPUrl($quality)
                            ];
                        });

                    $data['next_chapter'] = [
                        'id' => $nextChapter->id,
                        'preloaded_pages' => $nextPages
                    ];
                }
            }

            return $this->successResponse($data, 'Chapter data preloaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to preload chapter data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform chapter with detailed information
     */
    private function transformChapterDetailed(Chapter $chapter): array
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
            'manga' => [
                'id' => $chapter->manga->id,
                'title' => $chapter->manga->title,
                'slug' => $chapter->manga->slug,
                'reading_direction' => $chapter->manga->reading_direction,
                'url' => $chapter->manga->getUrl()
            ],
            'volume' => $chapter->volume ? [
                'id' => $chapter->volume->id,
                'volume_number' => $chapter->volume->volume_number,
                'title' => $chapter->volume->title
            ] : null,
            'pages' => $chapter->pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'page_number' => $page->page_number,
                    'image_url' => $page->image_url,
                    'optimized_urls' => [
                        'low' => $page->getOptimizedUrl('low'),
                        'medium' => $page->getOptimizedUrl('medium'),
                        'high' => $page->getOptimizedUrl('high')
                    ],
                    'thumbnail_url' => $page->getThumbnailUrl(),
                    'webp_url' => $page->getWebPUrl(),
                    'dimensions' => $page->getDimensions()
                ];
            }),
            'seo' => [
                'title' => $chapter->getSeoTitle(),
                'description' => $chapter->getSeoDescription(),
                'canonical_url' => $chapter->getCanonicalUrl(),
                'breadcrumbs' => $chapter->getBreadcrumbs()
            ],
            'created_at' => optional($chapter->created_at)->toISOString(),
            'updated_at' => optional($chapter->updated_at)->toISOString()
        ];
    }

    /**
     * Get recommended image quality based on user agent and connection
     */
    private function getRecommendedQuality(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        $saveData = $request->header('Save-Data', 'off');
        
        // Check for mobile devices or save-data header
        if ($saveData === 'on' || preg_match('/Mobile|Android|iPhone/', $userAgent)) {
            return 'low';
        }
        
        return 'medium';
    }

    /**
     * Check if client supports WebP format
     */
    private function supportsWebP(Request $request): bool
    {
        $accept = $request->header('Accept', '');
        return strpos($accept, 'image/webp') !== false;
    }
}