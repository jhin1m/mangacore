<?php

namespace Ophim\Core;

use Illuminate\Console\Scheduling\Schedule;
use Ophim\Core\Policies\PermissionPolicy;
use Ophim\Core\Policies\RolePolicy;
use Ophim\Core\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ophim\Core\Console\CreateUser;
use Ophim\Core\Console\InstallCommand;
use Ophim\Core\Console\GenerateMenuCommand;
use Ophim\Core\Console\MangaImportChapterCommand;
use Ophim\Core\Console\OptimizeImagesCommand;
use Ophim\Core\Console\GenerateThumbnailsCommand;
use Ophim\Core\Console\MangaMissingChaptersCommand;
use Ophim\Core\Console\MangaCleanCacheCommand;
use Ophim\Core\Middleware\CKFinderAuth;
use Ophim\Core\Middleware\ApiRateLimit;
// Manga-specific models
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Catalog;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Menu;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\ReadingProgress;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Theme;
use Ophim\Core\Models\Volume;
// Manga-specific policies
use Ophim\Core\Policies\ArtistPolicy;
use Ophim\Core\Policies\AuthorPolicy;
use Ophim\Core\Policies\CatalogPolicy;
use Ophim\Core\Policies\CategoryPolicy;
use Ophim\Core\Policies\ChapterPolicy;
use Ophim\Core\Policies\MangaPolicy;
use Ophim\Core\Policies\MenuPolicy;
use Ophim\Core\Policies\OriginPolicy;
use Ophim\Core\Policies\PagePolicy;
use Ophim\Core\Policies\PublisherPolicy;
use Ophim\Core\Policies\ReadingProgressPolicy;
use Ophim\Core\Policies\TagPolicy;
use Ophim\Core\Policies\VolumePolicy;

class OphimServiceProvider extends ServiceProvider
{
    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return [
            // Manga-specific models
            Artist::class => ArtistPolicy::class,
            Author::class => AuthorPolicy::class,
            Catalog::class => CatalogPolicy::class,
            Category::class => CategoryPolicy::class,
            Chapter::class => ChapterPolicy::class,
            Manga::class => MangaPolicy::class,
            Menu::class => MenuPolicy::class,
            Origin::class => OriginPolicy::class,
            Page::class => PagePolicy::class,
            Publisher::class => PublisherPolicy::class,
            ReadingProgress::class => ReadingProgressPolicy::class,
            Tag::class => TagPolicy::class,
            Volume::class => VolumePolicy::class
        ];
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'ophim');

        $this->mergeBackpackConfigs();

        $this->mergeCkfinderConfigs();

        $this->mergePolicies();
    }

    public function boot()
    {
        $this->registerPolicies();

        try {
            foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
                require_once $filename;
            }
        } catch (\Exception $e) {
            //throw $e;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->app->booted(function () {
            $this->loadThemeRoutes();
            $this->loadScheduler();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views/core/', 'ophim');

        $this->loadViewsFrom(__DIR__ . '/../resources/views/themes', 'themes');

        $this->publishFiles();

        $this->commands([
            InstallCommand::class,
            CreateUser::class,
            GenerateMenuCommand::class,
            MangaImportChapterCommand::class,
            OptimizeImagesCommand::class,
            GenerateThumbnailsCommand::class,
            MangaMissingChaptersCommand::class,
            MangaCleanCacheCommand::class,
            \Ophim\Core\Console\MangaCacheCommand::class,
        ]);

        // Register API middleware
        $this->app['router']->aliasMiddleware('api.rate_limit', ApiRateLimit::class);

        // Register view composers for manga reader
        $this->registerViewComposers();

        $this->bootSeoDefaults();
    }

    protected function publishFiles()
    {
        $backpack_menu_contents_view = [
            __DIR__ . '/../resources/views/core/base/'  => resource_path('views/vendor/hacoidev/base/'),
            __DIR__ . '/../resources/views/core/crud/'      => resource_path('views/vendor/hacoidev/crud/'),
        ];

        // Manga reader assets
        $manga_reader_assets = [
            __DIR__ . '/../resources/assets/js/manga-reader.js' => public_path('js/manga-reader.js'),
            __DIR__ . '/../resources/assets/css/reader.css' => public_path('css/reader.css'),
        ];

        $this->publishes($backpack_menu_contents_view, 'cms_menu_content');
        $this->publishes($manga_reader_assets, 'manga_reader');

        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('ophim.php')
        ], 'config');
    }

    protected function mergeBackpackConfigs()
    {
        config(['backpack.base.styles' => array_merge(config('backpack.base.styles', []), [
            'packages/select2/dist/css/select2.css',
            'packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css'
        ])]);

        config(['backpack.base.scripts' => array_merge(config('backpack.base.scripts', []), [
            'packages/select2/dist/js/select2.full.min.js'
        ])]);

        config(['backpack.base.middleware_class' => array_merge(config('backpack.base.middleware_class', []), [
            \Backpack\CRUD\app\Http\Middleware\UseBackpackAuthGuardInsteadOfDefaultAuthGuard::class,
        ])]);

        config(['cachebusting_string' => \PackageVersions\Versions::getVersion('hacoidev/crud')]);

        config(['backpack.base.project_logo' => '<b>Manga</b>Core']);
        config(['backpack.base.developer_name' => 'hacoidev']);
        config(['backpack.base.developer_link' => 'mailto:hacoi.dev@gmail.com']);
        config(['backpack.base.show_powered_by' => false]);
    }

    protected function mergeCkfinderConfigs()
    {
        config(['ckfinder.authentication' => CKFinderAuth::class]);
        config(['ckfinder.backends.default' => config('ophim.ckfinder.backends')]);
    }

    protected function mergePolicies()
    {
        config(['backpack.permissionmanager.policies.permission' => PermissionPolicy::class]);
        config(['backpack.permissionmanager.policies.role' => RolePolicy::class]);
        config(['backpack.permissionmanager.policies.user' => UserPolicy::class]);
    }

    protected function registerViewComposers()
    {
        // Register view composers for manga reader components
        view()->composer('ophim::reader.*', function ($view) {
            $view->with([
                'reading_modes' => config('ophim.manga.reading_modes', []),
                'image_quality_options' => config('ophim.image_processing.quality_settings', []),
                'cdn_enabled' => config('ophim.cdn.enabled', false)
            ]);
        });

        // Register view composers for manga management
        view()->composer('ophim::manga.*', function ($view) {
            $view->with([
                'manga_types' => config('ophim.manga.types', []),
                'manga_statuses' => config('ophim.manga.statuses', []),
                'demographics' => config('ophim.manga.demographics', []),
                'reading_directions' => config('ophim.manga.reading_directions', [])
            ]);
        });

        // Register view composers for chapter management
        view()->composer('ophim::chapters.*', function ($view) {
            $view->with([
                'batch_upload_config' => config('ophim.chapters.batch_upload', []),
                'supported_formats' => config('ophim.image_processing.supported_formats', [])
            ]);
        });
    }

    protected function bootSeoDefaults()
    {
        config([
            'seotools.meta.defaults.title' => setting('site_homepage_title'),
            'seotools.meta.defaults.description' => setting('site_meta_description'),
            'seotools.meta.defaults.keywords' => [setting('site_meta_keywords')],
            'seotools.meta.defaults.canonical' => url("/")
        ]);

        config([
            'seotools.opengraph.defaults.title' => setting('site_homepage_title'),
            'seotools.opengraph.defaults.description' => setting('site_meta_description'),
            'seotools.opengraph.defaults.type' => 'website',
            'seotools.opengraph.defaults.url' => url("/"),
            'seotools.opengraph.defaults.site_name' => setting('site_meta_siteName'),
            'seotools.opengraph.defaults.images' => [setting('site_meta_image')],
        ]);

        config([
            'seotools.twitter.defaults.card' => 'website',
            'seotools.twitter.defaults.title' => setting('site_homepage_title'),
            'seotools.twitter.defaults.description' => setting('site_meta_description'),
            'seotools.twitter.defaults.url' => url("/"),
            'seotools.twitter.defaults.site' => setting('site_meta_siteName'),
            'seotools.twitter.defaults.image' => setting('site_meta_image'),
        ]);

        config([
            'seotools.json-ld.defaults.title' => setting('site_homepage_title'),
            'seotools.json-ld.defaults.type' => 'WebPage',
            'seotools.json-ld.defaults.description' => setting('site_meta_description'),
            'seotools.json-ld.defaults.images' => setting('site_meta_image'),
        ]);
    }

    protected function loadThemeRoutes()
    {
        try {
            $activatedTheme = Theme::getActivatedTheme();
            if ($activatedTheme && file_exists($routeFile = base_path('vendor/' . $activatedTheme->package_name . '/routes/web.php'))) {
                $this->loadRoutesFrom($routeFile);
            }
            
            // Load manga reader routes from theme if available
            if ($activatedTheme && file_exists($readerRouteFile = base_path('vendor/' . $activatedTheme->package_name . '/routes/reader.php'))) {
                $this->loadRoutesFrom($readerRouteFile);
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::error('Failed to load theme routes: ' . $e->getMessage());
        }
    }

    protected function loadScheduler()
    {
        $schedule = $this->app->make(Schedule::class);

        // Reset daily view counts for manga
        $schedule->call(function () {
            if (Schema::hasTable('mangas')) {
                DB::table('mangas')->update(['view_day' => 0]);
            }
        })->daily();
        $schedule->call(function () {
            if (Schema::hasTable('mangas')) {
                DB::table('mangas')->update(['view_week' => 0]);
            }
        })->weekly();
        $schedule->call(function () {
            if (Schema::hasTable('mangas')) {
                DB::table('mangas')->update(['view_month' => 0]);
            }
        })->monthly();
    }
}