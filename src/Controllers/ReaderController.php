<?php

namespace Ophim\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ReadingProgress;

class ReaderController extends Controller
{
    /**
     * Display chapter with pages and navigation for manga reading
     *
     * @param Manga $manga
     * @param Chapter $chapter
     * @return View
     */
    public function show(Manga $manga, Chapter $chapter): View
    {
        // Verify chapter belongs to manga
        if ($chapter->manga_id !== $manga->id) {
            abort(404, 'Chapter not found for this manga');
        }

        // Check if chapter is published
        if (!$chapter->is_published) {
            abort(404, 'Chapter not yet published');
        }

        // Increment view count (with caching to prevent spam)
        $chapter->incrementViewCount();

        // Load chapter with pages
        $chapter->load(['pages' => function ($query) {
            $query->orderBy('page_number');
        }]);

        // Get navigation data
        $navigationData = $this->getNavigationData($chapter);

        // Get user's reading progress
        $readingProgress = ReadingProgress::getProgress($manga->id);

        // Get user's reading preferences
        $readingSettings = $this->getUserReadingSettings();

        // Preload next chapter data for smooth transition
        if ($navigationData['next_chapter']) {
            $this->preloadChapterData($navigationData['next_chapter']);
        }

        // Generate SEO tags
        $chapter->generateSeoTags();

        return view('core.reader.show', compact(
            'manga',
            'chapter', 
            'navigationData',
            'readingProgress',
            'readingSettings'
        ));
    }

    /**
     * API endpoint returning chapter pages and navigation info
     *
     * @param Chapter $chapter
     * @return JsonResponse
     */
    public function getChapterData(Chapter $chapter): JsonResponse
    {
        // Check if chapter is published
        if (!$chapter->is_published) {
            return response()->json(['error' => 'Chapter not available'], 404);
        }

        // Cache chapter data for performance
        $cacheKey = "chapter_data_{$chapter->id}";
        $chapterData = Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($chapter) {
            
            // Load pages with optimized URLs
            $pages = $chapter->pages()->orderBy('page_number')->get()->map(function ($page) {
                return [
                    'id' => $page->id,
                    'page_number' => $page->page_number,
                    'image_url' => $page->image_url,
                    'optimized_urls' => [
                        'low' => $page->getOptimizedUrl('low'),
                        'medium' => $page->getOptimizedUrl('medium'),
                        'high' => $page->getOptimizedUrl('high'),
                    ],
                    'webp_url' => $page->getWebPUrl(),
                    'thumbnail_url' => $page->getThumbnailUrl(),
                    'is_first_page' => $page->is_first_page,
                    'is_last_page' => $page->is_last_page,
                ];
            });

            return [
                'chapter' => [
                    'id' => $chapter->id,
                    'title' => $chapter->getFormattedTitle(),
                    'chapter_number' => $chapter->chapter_number,
                    'page_count' => $chapter->page_count,
                    'reading_direction' => $chapter->manga->reading_direction,
                ],
                'pages' => $pages,
                'navigation' => $this->getNavigationData($chapter),
            ];
        });

        return response()->json($chapterData);
    }

    /**
     * Save reading progress for tracking reading position
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveProgress(Request $request): JsonResponse
    {
        $request->validate([
            'manga_id' => 'required|integer|exists:mangas,id',
            'chapter_id' => 'required|integer|exists:chapters,id',
            'page_number' => 'required|integer|min:1',
        ]);

        $mangaId = $request->input('manga_id');
        $chapterId = $request->input('chapter_id');
        $pageNumber = $request->input('page_number');

        try {
            // Update reading progress
            $progress = ReadingProgress::updateProgress($mangaId, $chapterId, $pageNumber);

            // Determine if chapter is completed
            $chapter = Chapter::find($chapterId);
            $isCompleted = $pageNumber >= $chapter->page_count;

            return response()->json([
                'success' => true,
                'message' => 'Progress saved successfully',
                'data' => [
                    'manga_id' => $mangaId,
                    'chapter_id' => $chapterId,
                    'page_number' => $pageNumber,
                    'is_completed' => $isCompleted,
                    'progress_percentage' => ReadingProgress::getProgressPercentage($mangaId),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preloaded pages for smooth reading experience
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function preloadPages(Request $request): JsonResponse
    {
        $request->validate([
            'chapter_id' => 'required|integer|exists:chapters,id',
            'current_page' => 'required|integer|min:1',
            'buffer_size' => 'integer|min:1|max:10'
        ]);

        $chapterId = $request->input('chapter_id');
        $currentPage = $request->input('current_page');
        $bufferSize = $request->input('buffer_size', 3);

        $chapter = Chapter::find($chapterId);
        
        // Calculate preload range
        $startPage = max(1, $currentPage - 1);
        $endPage = min($chapter->page_count, $currentPage + $bufferSize);

        // Get pages in range
        $pages = $chapter->pages()
            ->whereBetween('page_number', [$startPage, $endPage])
            ->orderBy('page_number')
            ->get()
            ->map(function ($page) {
                return [
                    'page_number' => $page->page_number,
                    'image_url' => $page->getOptimizedUrl('medium'),
                    'webp_url' => $page->getWebPUrl(),
                ];
            });

        return response()->json([
            'success' => true,
            'pages' => $pages,
            'range' => [
                'start' => $startPage,
                'end' => $endPage,
                'current' => $currentPage
            ]
        ]);
    }

    /**
     * Update reading mode preferences
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateReadingMode(Request $request): JsonResponse
    {
        $request->validate([
            'reading_mode' => 'required|in:single,double,vertical,horizontal',
            'image_quality' => 'in:low,medium,high',
            'auto_scroll' => 'boolean',
            'manga_id' => 'integer|exists:mangas,id'
        ]);

        $settings = [
            'reading_mode' => $request->input('reading_mode'),
            'image_quality' => $request->input('image_quality', 'medium'),
            'auto_scroll' => $request->input('auto_scroll', false),
        ];

        // Save to user preferences or session
        if (Auth::check()) {
            // Save to user preferences (you might want to create a user_preferences table)
            $userId = Auth::id();
            $mangaId = $request->input('manga_id');
            
            if ($mangaId) {
                // Save manga-specific preferences
                Session::put("reading_settings_{$userId}_{$mangaId}", $settings);
            } else {
                // Save global preferences
                Session::put("reading_settings_{$userId}", $settings);
            }
        } else {
            // Save to session for guest users
            Session::put('reading_settings_guest', $settings);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reading preferences updated',
            'settings' => $settings
        ]);
    }

    /**
     * Get navigation data for chapter
     *
     * @param Chapter $chapter
     * @return array
     */
    protected function getNavigationData(Chapter $chapter): array
    {
        $manga = $chapter->manga;
        
        return Cache::remember("navigation_data_{$chapter->id}", setting('site_cache_ttl', 5 * 60), function () use ($chapter, $manga) {
            return [
                'manga' => [
                    'id' => $manga->id,
                    'title' => $manga->title,
                    'slug' => $manga->slug,
                    'url' => $manga->getUrl(),
                    'reading_direction' => $manga->reading_direction,
                ],
                'current_chapter' => [
                    'id' => $chapter->id,
                    'title' => $chapter->getFormattedTitle(),
                    'chapter_number' => $chapter->chapter_number,
                    'page_count' => $chapter->page_count,
                    'url' => $chapter->getUrl(),
                ],
                'previous_chapter' => $this->getChapterInfo($chapter->getPreviousChapter()),
                'next_chapter' => $this->getChapterInfo($chapter->getNextChapter()),
                'chapter_list' => $this->getChapterList($manga),
                'reading_progress' => ReadingProgress::getProgress($manga->id),
            ];
        });
    }

    /**
     * Get chapter info for navigation
     *
     * @param Chapter|null $chapter
     * @return array|null
     */
    protected function getChapterInfo(?Chapter $chapter): ?array
    {
        if (!$chapter) {
            return null;
        }

        return [
            'id' => $chapter->id,
            'title' => $chapter->getFormattedTitle(),
            'chapter_number' => $chapter->chapter_number,
            'url' => $chapter->getUrl(),
        ];
    }

    /**
     * Get chapter list for navigation dropdown
     *
     * @param Manga $manga
     * @return array
     */
    protected function getChapterList(Manga $manga): array
    {
        return Cache::remember("chapter_list_{$manga->id}", setting('site_cache_ttl', 5 * 60), function () use ($manga) {
            return $manga->chapters()
                ->published()
                ->orderBy('chapter_number', 'desc')
                ->get(['id', 'title', 'chapter_number', 'slug'])
                ->map(function ($chapter) {
                    return [
                        'id' => $chapter->id,
                        'title' => $chapter->getFormattedTitle(),
                        'chapter_number' => $chapter->chapter_number,
                        'url' => $chapter->getUrl(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Preload next chapter data for smooth reading experience
     *
     * @param array|null $nextChapterInfo
     * @return void
     */
    protected function preloadChapterData(?array $nextChapterInfo): void
    {
        if (!$nextChapterInfo) {
            return;
        }

        // Cache the next chapter's first few pages
        $nextChapter = Chapter::find($nextChapterInfo['id']);
        if ($nextChapter) {
            $cacheKey = "preload_chapter_{$nextChapter->id}";
            
            Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($nextChapter) {
                return $nextChapter->pages()
                    ->orderBy('page_number')
                    ->limit(3)
                    ->get(['page_number', 'image_url'])
                    ->map(function ($page) {
                        return [
                            'page_number' => $page->page_number,
                            'image_url' => $page->getOptimizedUrl('medium'),
                        ];
                    });
            });
        }
    }

    /**
     * Get user's reading settings
     *
     * @return array
     */
    protected function getUserReadingSettings(): array
    {
        $defaultSettings = [
            'reading_mode' => 'single',
            'image_quality' => 'medium',
            'auto_scroll' => false,
        ];

        if (Auth::check()) {
            $userId = Auth::id();
            return Session::get("reading_settings_{$userId}", $defaultSettings);
        }

        return Session::get('reading_settings_guest', $defaultSettings);
    }

    /**
     * Get reading settings for specific manga
     *
     * @param int $mangaId
     * @return array
     */
    protected function getMangaReadingSettings(int $mangaId): array
    {
        $globalSettings = $this->getUserReadingSettings();

        if (Auth::check()) {
            $userId = Auth::id();
            $mangaSettings = Session::get("reading_settings_{$userId}_{$mangaId}", []);
            return array_merge($globalSettings, $mangaSettings);
        }

        return $globalSettings;
    }

    /**
     * Handle reading mode switching
     *
     * @param Request $request
     * @param Chapter $chapter
     * @return JsonResponse
     */
    public function switchReadingMode(Request $request, Chapter $chapter): JsonResponse
    {
        $request->validate([
            'mode' => 'required|in:single,double,vertical,horizontal'
        ]);

        $mode = $request->input('mode');
        $manga = $chapter->manga;

        // Validate reading mode compatibility
        if ($mode === 'vertical' && $manga->reading_direction !== 'vertical') {
            return response()->json([
                'success' => false,
                'message' => 'Vertical mode not recommended for this manga type'
            ], 400);
        }

        // Get optimized page data for the new reading mode
        $pages = $chapter->pages()->orderBy('page_number')->get();
        $optimizedPages = $pages->map(function ($page) use ($mode) {
            $quality = $mode === 'double' ? 'high' : 'medium';
            
            return [
                'page_number' => $page->page_number,
                'image_url' => $page->getOptimizedUrl($quality),
                'webp_url' => $page->getWebPUrl(),
            ];
        });

        return response()->json([
            'success' => true,
            'mode' => $mode,
            'pages' => $optimizedPages,
            'reading_direction' => $manga->reading_direction,
        ]);
    }
}