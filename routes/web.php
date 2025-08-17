<?php

use Illuminate\Support\Facades\Route;
use Ophim\Core\Controllers\ReaderController;
use Ophim\Core\Controllers\MangaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| Note: These routes are basic examples. Themes should override these
| with their own implementations.
*/

// Manga listing routes
Route::get('/danh-sach/{type}', [MangaController::class, 'index'])
    ->name('types.manga.index')
    ->where(['type' => '[a-zA-Z0-9\-_]+']);

Route::get('/manga', [MangaController::class, 'index'])
    ->name('manga.index');

// Taxonomy listing routes
Route::get('/tac-gia/{author}', [MangaController::class, 'byAuthor'])
    ->name('authors.manga.index')
    ->where(['author' => '[a-zA-Z0-9\-_]+']);

Route::get('/hoa-si/{artist}', [MangaController::class, 'byArtist'])
    ->name('artists.manga.index')
    ->where(['artist' => '[a-zA-Z0-9\-_]+']);

Route::get('/nha-xuat-ban/{publisher}', [MangaController::class, 'byPublisher'])
    ->name('publishers.manga.index')
    ->where(['publisher' => '[a-zA-Z0-9\-_]+']);

Route::get('/xuat-xu/{origin}', [MangaController::class, 'byOrigin'])
    ->name('origins.manga.index')
    ->where(['origin' => '[a-zA-Z0-9\-_]+']);

Route::get('/the-loai/{category}', [MangaController::class, 'byCategory'])
    ->name('categories.manga.index')
    ->where(['category' => '[a-zA-Z0-9\-_]+']);

Route::get('/tag/{tag}', [MangaController::class, 'byTag'])
    ->name('tags.manga.index')
    ->where(['tag' => '[a-zA-Z0-9\-_]+']);

// Chapter reading routes
Route::get('/manga/{manga}/chapter-{chapter}-{id}', [ReaderController::class, 'show'])
    ->name('chapters.show')
    ->where(['manga' => '[a-zA-Z0-9\-_]+', 'chapter' => '[a-zA-Z0-9\-_.]+', 'id' => '[0-9]+']);

Route::get('/manga/{manga}', [MangaController::class, 'show'])
    ->name('manga.show')
    ->where(['manga' => '[a-zA-Z0-9\-_]+']);

// API routes for reader functionality
Route::prefix('api')->middleware(['web'])->group(function () {
    Route::get('/chapters/{chapter}/data', [ReaderController::class, 'getChapterData'])
        ->name('api.chapters.data');
    
    Route::post('/reading/progress', [ReaderController::class, 'saveProgress'])
        ->name('api.reading.progress');
    
    Route::post('/chapters/preload', [ReaderController::class, 'preloadPages'])
        ->name('api.chapters.preload');
    
    Route::post('/reading/settings', [ReaderController::class, 'updateReadingMode'])
        ->name('api.reading.settings');
    
    Route::post('/chapters/{chapter}/reading-mode', [ReaderController::class, 'switchReadingMode'])
        ->name('api.chapters.reading-mode');
    
    // Manga API routes
    Route::get('/manga/{manga}/chapters', [MangaController::class, 'chapters'])
        ->name('api.manga.chapters');
    
    Route::get('/manga/{manga}/chapters-by-volume', [MangaController::class, 'chaptersByVolume'])
        ->name('api.manga.chapters-by-volume');
    
    Route::get('/manga/{manga}/statistics', [MangaController::class, 'statistics'])
        ->name('api.manga.statistics');
    
    Route::get('/manga/{manga}/reading-history', [MangaController::class, 'readingHistory'])
        ->name('api.manga.reading-history');
    
    Route::get('/manga/{manga}/recommendations', [MangaController::class, 'recommendations'])
        ->name('api.manga.recommendations');
    
    Route::post('/manga/{manga}/bookmark', [MangaController::class, 'toggleBookmark'])
        ->name('api.manga.bookmark');
});