<?php

use Illuminate\Support\Facades\Route;
use Ophim\Core\Controllers\Api\MangaApiController;
use Ophim\Core\Controllers\Api\ChapterApiController;
use Ophim\Core\Controllers\Api\ReadingProgressApiController;
use Ophim\Core\Controllers\Api\UserPreferencesApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->middleware(['web', 'api.rate_limit:120,1'])->group(function () {
    
    // Manga API endpoints
    Route::prefix('manga')->group(function () {
        // Manga listing with filtering, sorting, and pagination
        Route::get('/', [MangaApiController::class, 'index'])->name('api.manga.index');
        
        // Manga details
        Route::get('/{manga}', [MangaApiController::class, 'show'])->name('api.manga.show');
        
        // Manga chapters
        Route::get('/{manga}/chapters', [MangaApiController::class, 'chapters'])->name('api.manga.chapters');
        
        // Manga chapters organized by volume
        Route::get('/{manga}/volumes', [MangaApiController::class, 'volumes'])->name('api.manga.volumes');
        
        // Manga statistics
        Route::get('/{manga}/stats', [MangaApiController::class, 'statistics'])->name('api.manga.statistics');
        
        // Related manga recommendations
        Route::get('/{manga}/related', [MangaApiController::class, 'related'])->name('api.manga.related');
        
        // Search manga
        Route::get('/search/{query}', [MangaApiController::class, 'search'])->name('api.manga.search');
    });
    
    // Chapter API endpoints
    Route::prefix('chapters')->group(function () {
        // Chapter details with pages
        Route::get('/{chapter}', [ChapterApiController::class, 'show'])->name('api.chapters.show');
        
        // Chapter pages with navigation
        Route::get('/{chapter}/pages', [ChapterApiController::class, 'pages'])->name('api.chapters.pages');
        
        // Chapter navigation data
        Route::get('/{chapter}/navigation', [ChapterApiController::class, 'navigation'])->name('api.chapters.navigation');
        
        // Preload chapter data
        Route::post('/{chapter}/preload', [ChapterApiController::class, 'preload'])->name('api.chapters.preload');
    });
    
    // Reading Progress API endpoints (requires authentication)
    Route::middleware(['auth:sanctum'])->prefix('reading')->group(function () {
        // Get user's reading progress
        Route::get('/progress', [ReadingProgressApiController::class, 'index'])->name('api.reading.progress.index');
        
        // Get reading progress for specific manga
        Route::get('/progress/{manga}', [ReadingProgressApiController::class, 'show'])->name('api.reading.progress.show');
        
        // Save reading progress
        Route::post('/progress', [ReadingProgressApiController::class, 'store'])->name('api.reading.progress.store');
        
        // Update reading progress
        Route::put('/progress/{manga}', [ReadingProgressApiController::class, 'update'])->name('api.reading.progress.update');
        
        // Delete reading progress
        Route::delete('/progress/{manga}', [ReadingProgressApiController::class, 'destroy'])->name('api.reading.progress.destroy');
        
        // Get reading history
        Route::get('/history', [ReadingProgressApiController::class, 'history'])->name('api.reading.history');
        
        // Get reading statistics
        Route::get('/stats', [ReadingProgressApiController::class, 'statistics'])->name('api.reading.statistics');
        
        // Bookmark functionality
        Route::post('/bookmarks', [ReadingProgressApiController::class, 'addBookmark'])->name('api.reading.bookmarks.add');
        Route::delete('/bookmarks/{manga}', [ReadingProgressApiController::class, 'removeBookmark'])->name('api.reading.bookmarks.remove');
        Route::get('/bookmarks', [ReadingProgressApiController::class, 'bookmarks'])->name('api.reading.bookmarks.index');
        
        // Continue reading list
        Route::get('/continue', [ReadingProgressApiController::class, 'continueReading'])->name('api.reading.continue');
    });
    
    // Public reading progress endpoints (for guest users using session)
    Route::prefix('reading/guest')->group(function () {
        // Save guest reading progress
        Route::post('/progress', [ReadingProgressApiController::class, 'storeGuest'])->name('api.reading.progress.guest');
        
        // Get guest reading progress
        Route::get('/progress/{sessionId}', [ReadingProgressApiController::class, 'showGuest'])->name('api.reading.progress.guest.show');
    });
    
    // User Preferences and Ratings API endpoints (requires authentication)
    Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
        // User preferences
        Route::get('/preferences', [UserPreferencesApiController::class, 'index'])->name('api.user.preferences.index');
        Route::put('/preferences', [UserPreferencesApiController::class, 'update'])->name('api.user.preferences.update');
        
        // Rating and favorites
        Route::post('/manga/{manga}/rate', [UserPreferencesApiController::class, 'rateManga'])->name('api.user.manga.rate');
        Route::post('/manga/{manga}/favorite', [UserPreferencesApiController::class, 'addToFavorites'])->name('api.user.manga.favorite.add');
        Route::delete('/manga/{manga}/favorite', [UserPreferencesApiController::class, 'removeFromFavorites'])->name('api.user.manga.favorite.remove');
        
        // User lists
        Route::get('/favorites', [UserPreferencesApiController::class, 'favorites'])->name('api.user.favorites');
        Route::get('/ratings', [UserPreferencesApiController::class, 'ratings'])->name('api.user.ratings');
        Route::get('/recently-read', [UserPreferencesApiController::class, 'recentlyRead'])->name('api.user.recently-read');
        
        // User statistics
        Route::get('/statistics', [UserPreferencesApiController::class, 'statistics'])->name('api.user.statistics');
    });

    // Categories and taxonomies
    Route::prefix('taxonomies')->group(function () {
        Route::get('/categories', [MangaApiController::class, 'categories'])->name('api.taxonomies.categories');
        Route::get('/tags', [MangaApiController::class, 'tags'])->name('api.taxonomies.tags');
        Route::get('/authors', [MangaApiController::class, 'authors'])->name('api.taxonomies.authors');
        Route::get('/artists', [MangaApiController::class, 'artists'])->name('api.taxonomies.artists');
        Route::get('/publishers', [MangaApiController::class, 'publishers'])->name('api.taxonomies.publishers');
        Route::get('/origins', [MangaApiController::class, 'origins'])->name('api.taxonomies.origins');
    });
});

// Rate limiting for unauthenticated users
Route::prefix('v1/public')->middleware(['web', 'api.rate_limit:60,1'])->group(function () {
    // Public endpoints with stricter rate limiting
    Route::get('/manga/popular', [MangaApiController::class, 'popular'])->name('api.public.manga.popular');
    Route::get('/manga/latest', [MangaApiController::class, 'latest'])->name('api.public.manga.latest');
    Route::get('/manga/featured', [MangaApiController::class, 'featured'])->name('api.public.manga.featured');
});