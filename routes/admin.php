<?php

use Illuminate\Support\Facades\Route;
use CKSource\CKFinderBridge\Controller\CKFinderController;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'Ophim\Core\Controllers\Admin',
], function () {
    if (config('backpack.base.setup_dashboard_routes')) {
        Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
        Route::get('/', 'AdminController@redirect')->name('backpack');
    }

    Route::crud('catalog', 'CatalogCrudController');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('manga', 'MangaCrudController');
    
    // Legacy movie-related routes have been removed during manga refactor:
    // - Route::crud('movie', 'MovieCrudController') - replaced by manga routes
    // - Route::crud('actor', 'ActorCrudController') - replaced by author routes  
    // - Route::crud('director', 'DirectorCrudController') - replaced by artist routes
    // - Route::crud('region', 'RegionCrudController') - replaced by origin routes
    // - Route::crud('studio', 'StudioCrudController') - replaced by publisher routes
    
    // Manga-specific CRUD controllers
    Route::crud('author', 'AuthorCrudController');
    Route::crud('artist', 'ArtistCrudController');
    Route::crud('publisher', 'PublisherCrudController');
    Route::crud('origin', 'OriginCrudController');
    Route::crud('tag', 'TagCrudController');
    Route::crud('menu', 'MenuCrudController');
    Route::crud('chapter', 'ChapterCrudController');
    
    // Chapter management routes
    Route::post('chapter/batch-upload', 'ChapterCrudController@batchUpload');
    Route::post('chapter/reorder-pages', 'ChapterCrudController@reorderPages');
    Route::delete('chapter/delete-page/{pageId}', 'ChapterCrudController@deletePage');
    Route::post('chapter/optimize-images/{chapterId}', 'ChapterCrudController@optimizeImages');
    Route::post('chapter/schedule-publishing', 'ChapterCrudController@schedulePublishing');
    
    Route::crud('theme', 'ThemeManagementController');
    Route::crud('sitemap', 'SiteMapController');
    Route::get('quick-action/delete-cache', 'QuickActionController@delete_cache');
});

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        [
            \Ophim\Core\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ],
        (array) config('backpack.base.middleware_key', 'admin')
    ),
], function () {
    Route::prefix('/ckfinder')->group(function () {
        Route::any('/connector', [CKFinderController::class, 'requestAction'])->name('ckfinder_connector');
        Route::any('/browser', [CKFinderController::class, 'browserAction'])->name('ckfinder_browser');
    });
});
