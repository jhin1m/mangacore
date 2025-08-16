<?php

namespace Ophim\Core\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Ophim\Core\Models\ReadingProgress;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class ReadingProgressApiController extends BaseApiController
{
    /**
     * Get user's reading progress for all manga
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = ReadingProgress::with(['manga', 'chapter'])
                ->where('user_id', $user->id);

            // Apply sorting
            $sortParams = $this->getSortingParams($request, [
                'updated_at', 'created_at'
            ]);
            $query->orderBy($sortParams['sort_by'], $sortParams['sort_order']);

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $progress = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $progress->getCollection()->map(function ($item) {
                return $this->transformReadingProgress($item);
            });

            $progress->setCollection($transformedData);

            return $this->paginatedResponse($progress, 'Reading progress retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get reading progress for specific manga
     */
    public function show(Manga $manga): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $progress = ReadingProgress::with(['chapter', 'manga'])
                ->where('user_id', $user->id)
                ->where('manga_id', $manga->id)
                ->first();

            if (!$progress) {
                return $this->successResponse(null, 'No reading progress found for this manga');
            }

            $data = $this->transformReadingProgress($progress, true);

            return $this->successResponse($data, 'Reading progress retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Save reading progress
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'manga_id' => 'required|exists:mangas,id',
                'chapter_id' => 'required|exists:chapters,id',
                'page_number' => 'required|integer|min:1',
                'completed_at' => 'nullable|date'
            ]);

            $user = Auth::user();

            // Verify chapter belongs to manga
            $chapter = Chapter::where('id', $validated['chapter_id'])
                ->where('manga_id', $validated['manga_id'])
                ->first();

            if (!$chapter) {
                return $this->errorResponse('Chapter does not belong to the specified manga', 400);
            }

            // Verify page number is valid
            if ($validated['page_number'] > $chapter->page_count) {
                return $this->errorResponse('Invalid page number', 400);
            }

            // Update or create reading progress
            $progress = ReadingProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'manga_id' => $validated['manga_id']
                ],
                [
                    'chapter_id' => $validated['chapter_id'],
                    'page_number' => $validated['page_number'],
                    'completed_at' => $validated['completed_at'] ?? 
                        ($validated['page_number'] >= $chapter->page_count ? now() : null)
                ]
            );

            $progress->load(['manga', 'chapter']);
            $data = $this->transformReadingProgress($progress, true);

            return $this->successResponse($data, 'Reading progress saved successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update reading progress
     */
    public function update(Request $request, Manga $manga): JsonResponse
    {
        try {
            $validated = $request->validate([
                'chapter_id' => 'required|exists:chapters,id',
                'page_number' => 'required|integer|min:1',
                'completed_at' => 'nullable|date'
            ]);

            $user = Auth::user();

            // Verify chapter belongs to manga
            $chapter = Chapter::where('id', $validated['chapter_id'])
                ->where('manga_id', $manga->id)
                ->first();

            if (!$chapter) {
                return $this->errorResponse('Chapter does not belong to this manga', 400);
            }

            // Find existing progress
            $progress = ReadingProgress::where('user_id', $user->id)
                ->where('manga_id', $manga->id)
                ->first();

            if (!$progress) {
                return $this->errorResponse('No reading progress found for this manga', 404);
            }

            // Update progress
            $progress->update([
                'chapter_id' => $validated['chapter_id'],
                'page_number' => $validated['page_number'],
                'completed_at' => $validated['completed_at'] ?? 
                    ($validated['page_number'] >= $chapter->page_count ? now() : null)
            ]);

            $progress->load(['manga', 'chapter']);
            $data = $this->transformReadingProgress($progress, true);

            return $this->successResponse($data, 'Reading progress updated successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete reading progress
     */
    public function destroy(Manga $manga): JsonResponse
    {
        try {
            $user = Auth::user();

            $progress = ReadingProgress::where('user_id', $user->id)
                ->where('manga_id', $manga->id)
                ->first();

            if (!$progress) {
                return $this->errorResponse('No reading progress found for this manga', 404);
            }

            $progress->delete();

            return $this->successResponse(null, 'Reading progress deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get reading history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = ReadingProgress::with(['manga', 'chapter'])
                ->where('user_id', $user->id)
                ->orderBy('updated_at', 'desc');

            // Filter by date range if provided
            if ($request->has('from_date')) {
                $query->where('updated_at', '>=', $request->get('from_date'));
            }
            
            if ($request->has('to_date')) {
                $query->where('updated_at', '<=', $request->get('to_date'));
            }

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $history = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $history->getCollection()->map(function ($item) {
                return $this->transformReadingProgress($item);
            });

            $history->setCollection($transformedData);

            return $this->paginatedResponse($history, 'Reading history retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reading history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get reading statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total_manga_read' => ReadingProgress::where('user_id', $user->id)->count(),
                'completed_manga' => ReadingProgress::where('user_id', $user->id)
                    ->whereNotNull('completed_at')->count(),
                'total_chapters_read' => ReadingProgress::where('user_id', $user->id)
                    ->distinct('chapter_id')->count(),
                'reading_streak' => $this->calculateReadingStreak($user->id),
                'favorite_genres' => $this->getFavoriteGenres($user->id),
                'reading_activity' => $this->getReadingActivity($user->id),
                'monthly_stats' => $this->getMonthlyStats($user->id)
            ];

            return $this->successResponse($stats, 'Reading statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reading statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Save guest reading progress (session-based)
     */
    public function storeGuest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'manga_id' => 'required|exists:mangas,id',
                'chapter_id' => 'required|exists:chapters,id',
                'page_number' => 'required|integer|min:1'
            ]);

            // Store in session
            $sessionKey = "guest_progress_{$validated['session_id']}";
            $progress = Session::get($sessionKey, []);
            
            $progress[$validated['manga_id']] = [
                'chapter_id' => $validated['chapter_id'],
                'page_number' => $validated['page_number'],
                'updated_at' => now()->toISOString()
            ];
            
            Session::put($sessionKey, $progress);

            return $this->successResponse([
                'session_id' => $validated['session_id'],
                'manga_id' => $validated['manga_id'],
                'progress' => $progress[$validated['manga_id']]
            ], 'Guest reading progress saved successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save guest reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get guest reading progress
     */
    public function showGuest(string $sessionId): JsonResponse
    {
        try {
            $sessionKey = "guest_progress_{$sessionId}";
            $progress = Session::get($sessionKey, []);

            return $this->successResponse([
                'session_id' => $sessionId,
                'progress' => $progress
            ], 'Guest reading progress retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve guest reading progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform reading progress for API response
     */
    private function transformReadingProgress(ReadingProgress $progress, bool $detailed = false): array
    {
        $data = [
            'id' => $progress->id,
            'manga' => [
                'id' => $progress->manga->id,
                'title' => $progress->manga->title,
                'slug' => $progress->manga->slug,
                'cover_image' => $progress->manga->getCoverUrl(),
                'url' => $progress->manga->getUrl()
            ],
            'chapter' => [
                'id' => $progress->chapter->id,
                'title' => $progress->chapter->title,
                'chapter_number' => $progress->chapter->chapter_number,
                'page_count' => $progress->chapter->page_count,
                'url' => $progress->chapter->getUrl()
            ],
            'page_number' => $progress->page_number,
            'progress_percentage' => round(($progress->page_number / $progress->chapter->page_count) * 100, 2),
            'completed_at' => $progress->completed_at?->toISOString(),
            'updated_at' => $progress->updated_at?->toISOString()
        ];

        if ($detailed) {
            // Add additional details for single progress view
            $data['reading_stats'] = [
                'is_completed' => !is_null($progress->completed_at),
                'pages_remaining' => max(0, $progress->chapter->page_count - $progress->page_number),
                'estimated_reading_time' => $this->estimateReadingTime($progress->chapter->page_count - $progress->page_number)
            ];
        }

        return $data;
    }

    /**
     * Calculate reading streak
     */
    private function calculateReadingStreak(int $userId): int
    {
        // Implementation for calculating consecutive days of reading
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        while (true) {
            $hasProgress = ReadingProgress::where('user_id', $userId)
                ->whereDate('updated_at', $currentDate)
                ->exists();
                
            if (!$hasProgress) {
                break;
            }
            
            $streak++;
            $currentDate->subDay();
        }
        
        return $streak;
    }

    /**
     * Get favorite genres based on reading history
     */
    private function getFavoriteGenres(int $userId): array
    {
        // Implementation for analyzing favorite genres
        return ReadingProgress::where('user_id', $userId)
            ->join('mangas', 'reading_progress.manga_id', '=', 'mangas.id')
            ->join('category_manga', 'mangas.id', '=', 'category_manga.manga_id')
            ->join('categories', 'category_manga.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get reading activity for the last 30 days
     */
    private function getReadingActivity(int $userId): array
    {
        $activity = [];
        $startDate = now()->subDays(29)->startOfDay();
        
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = ReadingProgress::where('user_id', $userId)
                ->whereDate('updated_at', $date)
                ->count();
                
            $activity[] = [
                'date' => $date->toDateString(),
                'count' => $count
            ];
        }
        
        return $activity;
    }

    /**
     * Get monthly reading statistics
     */
    private function getMonthlyStats(int $userId): array
    {
        return [
            'this_month' => ReadingProgress::where('user_id', $userId)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'last_month' => ReadingProgress::where('user_id', $userId)
                ->whereMonth('updated_at', now()->subMonth()->month)
                ->whereYear('updated_at', now()->subMonth()->year)
                ->count()
        ];
    }

    /**
     * Estimate reading time in minutes
     */
    private function estimateReadingTime(int $pages): int
    {
        // Assume 30 seconds per page on average
        return max(1, round($pages * 0.5));
    }

    /**
     * Add bookmark to reading progress
     */
    public function addBookmark(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'manga_id' => 'required|exists:mangas,id',
                'chapter_id' => 'required|exists:chapters,id',
                'page_number' => 'required|integer|min:1',
                'note' => 'nullable|string|max:500'
            ]);

            $user = Auth::user();

            // Verify chapter belongs to manga
            $chapter = Chapter::where('id', $validated['chapter_id'])
                ->where('manga_id', $validated['manga_id'])
                ->first();

            if (!$chapter) {
                return $this->errorResponse('Chapter does not belong to the specified manga', 400);
            }

            $progress = ReadingProgress::addBookmark(
                $validated['manga_id'],
                $validated['chapter_id'],
                $validated['page_number'],
                $validated['note'] ?? null,
                $user->id
            );

            if (!$progress) {
                return $this->errorResponse('Failed to add bookmark', 500);
            }

            $progress->load(['manga', 'chapter']);
            $data = $this->transformReadingProgress($progress, true);
            $data['bookmark'] = [
                'is_bookmarked' => $progress->is_bookmarked,
                'note' => $progress->bookmark_note
            ];

            return $this->successResponse($data, 'Bookmark added successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add bookmark: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove bookmark from reading progress
     */
    public function removeBookmark(Manga $manga): JsonResponse
    {
        try {
            $user = Auth::user();

            $result = ReadingProgress::removeBookmark($manga->id, $user->id);

            if (!$result) {
                return $this->errorResponse('No bookmark found for this manga', 404);
            }

            return $this->successResponse(null, 'Bookmark removed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove bookmark: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's bookmarks
     */
    public function bookmarks(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = ReadingProgress::getUserBookmarks($user->id);

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Convert to paginated collection
            $bookmarks = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $bookmarks->getCollection()->map(function ($item) {
                $data = $this->transformReadingProgress($item);
                $data['bookmark'] = [
                    'is_bookmarked' => $item->is_bookmarked,
                    'note' => $item->bookmark_note,
                    'bookmarked_at' => $item->updated_at->toISOString()
                ];
                return $data;
            });

            $bookmarks->setCollection($transformedData);

            return $this->paginatedResponse($bookmarks, 'Bookmarks retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve bookmarks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get continue reading list
     */
    public function continueReading(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = ReadingProgress::with(['manga', 'chapter'])
                ->where('user_id', $user->id)
                ->whereHas('manga', function ($query) {
                    $query->where('is_completed', false);
                })
                ->orderBy('updated_at', 'desc');

            // Get pagination params
            $paginationParams = $this->getPaginationParams($request);
            
            // Paginate results
            $continueReading = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedData = $continueReading->getCollection()->map(function ($item) {
                $data = $this->transformReadingProgress($item);
                
                // Add next chapter info if available
                $nextChapter = Chapter::where('manga_id', $item->manga_id)
                    ->where('chapter_number', '>', $item->chapter->chapter_number)
                    ->orderBy('chapter_number')
                    ->first();

                if ($nextChapter) {
                    $data['next_chapter'] = [
                        'id' => $nextChapter->id,
                        'title' => $nextChapter->title,
                        'chapter_number' => $nextChapter->chapter_number,
                        'url' => $nextChapter->getUrl()
                    ];
                }

                return $data;
            });

            $continueReading->setCollection($transformedData);

            return $this->paginatedResponse($continueReading, 'Continue reading list retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve continue reading list: ' . $e->getMessage(), 500);
        }
    }
}