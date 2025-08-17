<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Ophim\Core\Contracts\HasUrlInterface;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Contracts\SeoInterface;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Traits\HasTitle;
use Illuminate\Support\Str;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;

class Chapter extends Model implements Cacheable, HasUrlInterface, SeoInterface
{
    use CrudTrait;
    use HasFactory;
    use HasCache;
    use HasTitle;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'chapters';
    protected $guarded = ['id'];

    protected $fillable = [
        'manga_id',
        'volume_id', 
        'title',
        'slug',
        'chapter_number',
        'volume_number',
        'page_count',
        'view_count',
        'published_at',
        'is_premium'
    ];

    protected $casts = [
        'chapter_number' => 'decimal:1',
        'published_at' => 'datetime',
        'is_premium' => 'boolean'
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function boot()
    {
        parent::boot();

        // Invalidate caches when chapter is updated or deleted
        static::updated(function ($instance) {
            $instance->invalidateCache();
        });

        static::deleted(function ($instance) {
            $instance->invalidateCache();
        });

        // Update manga page count when chapter is created or deleted
        static::created(function ($instance) {
            $instance->manga->invalidateCache();
        });
    }

    /**
     * Generate URL for chapter reading page
     */
    public function getUrl()
    {
        $manga = Cache::remember("cache_manga_by_id:" . $this->manga_id, setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga;
        });

        $params = [];
        $site_routes_chapter = setting('site_routes_chapter', '/manga/{manga}/chapter-{chapter}-{id}');
        
        if (strpos($site_routes_chapter, '{manga}')) $params['manga'] = $manga->slug;
        if (strpos($site_routes_chapter, '{manga_id}')) $params['manga_id'] = $manga->id;
        if (strpos($site_routes_chapter, '{chapter}')) $params['chapter'] = $this->slug ?: $this->chapter_number;
        if (strpos($site_routes_chapter, '{id}')) $params['id'] = $this->id;

        return route('chapters.show', $params);
    }

    /**
     * Admin button to open chapter in new tab
     */
    public function openChapter($crud = false)
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="' . $this->getUrl() . '" data-toggle="tooltip" title="Open chapter reader"><i class="fa fa-book-open"></i> Read</a>';
    }

    /**
     * Get the title pattern for SEO
     */
    protected function titlePattern(): string
    {
        return Setting::get('site_chapter_read_title', 'Chapter {chapter_number}: {title} - {manga_title}');
    }

    /**
     * Get formatted chapter title
     */
    public function getFormattedTitle()
    {
        if ($this->title) {
            return "Chapter {$this->chapter_number}: {$this->title}";
        }
        return "Chapter {$this->chapter_number}";
    }

    /**
     * Get next chapter in sequence
     */
    public function getNextChapter()
    {
        return Cache::remember("next_chapter_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga->chapters()
                ->where('chapter_number', '>', $this->chapter_number)
                ->where('published_at', '<=', now())
                ->orderBy('chapter_number', 'asc')
                ->first();
        });
    }

    /**
     * Get previous chapter in sequence
     */
    public function getPreviousChapter()
    {
        return Cache::remember("prev_chapter_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga->chapters()
                ->where('chapter_number', '<', $this->chapter_number)
                ->where('published_at', '<=', now())
                ->orderBy('chapter_number', 'desc')
                ->first();
        });
    }

    /**
     * Generate SEO tags for chapter pages
     */
    public function generateSeoTags()
    {
        $manga = $this->manga;
        $manga_cover_url = filter_var($manga->cover_image, FILTER_VALIDATE_URL) ? $manga->cover_image : request()->root() . $manga->cover_image;
        $manga_banner_url = filter_var($manga->banner_image, FILTER_VALIDATE_URL) ? $manga->banner_image : request()->root() . $manga->banner_image;
        $manga_description = Str::limit(strip_tags($manga->description), 150, '...');
        $chapter_url = $this->getUrl();
        $chapter_title = $this->getTitle();
        $site_meta_siteName = setting('site_meta_siteName');

        // Meta tags
        SEOMeta::setTitle($chapter_title, false)
            ->setDescription($manga_description)
            ->addKeyword($manga->tags()->pluck('name')->toArray())
            ->addMeta('article:published_time', $this->published_at ? $this->published_at->toW3CString() : $this->created_at->toW3CString(), 'property')
            ->addMeta('article:section', $manga->categories->pluck('name')->join(","), 'property')
            ->addMeta('article:tag', $manga->tags->pluck('name')->join(","), 'property')
            ->setCanonical($chapter_url);

        // Enhanced Open Graph tags for chapter
        OpenGraph::setType('article')
            ->setSiteName($site_meta_siteName)
            ->setTitle($chapter_title, false)
            ->addProperty('locale', 'vi-VN')
            ->addProperty('url', $chapter_url)
            ->addProperty('updated_time', $this->updated_at->toW3CString())
            ->setDescription($manga_description)
            ->addImages([$manga_cover_url, $manga_banner_url])
            ->addProperty('article:author', $manga->authors->pluck('name')->join(", "))
            ->addProperty('article:published_time', $this->published_at ? $this->published_at->toW3CString() : $this->created_at->toW3CString())
            ->addProperty('article:modified_time', $this->updated_at->toW3CString())
            ->addProperty('article:section', $manga->categories->pluck('name')->join(", "))
            ->addProperty('article:tag', $manga->tags->pluck('name')->join(", "))
            ->addProperty('og:type', 'article')
            ->addProperty('og:book:author', $manga->authors->pluck('name')->join(", "));

        // Enhanced Twitter Card for chapter
        TwitterCard::setSite($site_meta_siteName)
            ->setTitle($chapter_title, false)
            ->setType('summary_large_image')
            ->setImage($manga_cover_url)
            ->setDescription($manga_description)
            ->setUrl($chapter_url)
            ->addValue('twitter:label1', 'Chapter')
            ->addValue('twitter:data1', number_format($this->chapter_number, 1))
            ->addValue('twitter:label2', 'Pages')
            ->addValue('twitter:data2', $this->page_count ?: 'N/A');

        // Enhanced JSON-LD structured data for Chapter
        JsonLdMulti::newJsonLd()
            ->setSite($site_meta_siteName)
            ->addValue('@context', 'https://schema.org')
            ->addValue('@type', 'Chapter')
            ->addValue('name', $chapter_title)
            ->addValue('description', $manga_description)
            ->addValue('url', $chapter_url)
            ->addValue('image', [$manga_cover_url, $manga_banner_url])
            ->addValue('dateCreated', $this->created_at->toW3CString())
            ->addValue('dateModified', $this->updated_at->toW3CString())
            ->addValue('datePublished', ($this->published_at ?: $this->created_at)->toW3CString())
            ->addValue('position', $this->chapter_number)
            ->addValue('pageStart', 1)
            ->addValue('pageEnd', $this->page_count ?: 1)
            ->addValue('pagination', $this->page_count ?: 1)
            ->addValue('inLanguage', 'vi-VN')
            ->addValue('isPartOf', [
                '@type' => 'Book',
                'name' => $manga->title,
                'url' => $manga->getUrl(),
                'bookFormat' => 'https://schema.org/GraphicNovel',
                'author' => $manga->authors->map(function ($author) {
                    return [
                        '@type' => 'Person', 
                        'name' => $author->name,
                        'url' => $author->getUrl()
                    ];
                })->toArray(),
                'illustrator' => $manga->artists->map(function ($artist) {
                    return [
                        '@type' => 'Person', 
                        'name' => $artist->name,
                        'url' => $artist->getUrl()
                    ];
                })->toArray(),
                'publisher' => $manga->publishers->map(function ($publisher) {
                    return [
                        '@type' => 'Organization', 
                        'name' => $publisher->name,
                        'url' => $publisher->getUrl()
                    ];
                })->toArray(),
                'genre' => $manga->categories->pluck('name')->toArray()
            ]);

        // Breadcrumb structured data
        $this->generateBreadcrumbSeo();
    }

    /**
     * Generate breadcrumb structured data
     */
    protected function generateBreadcrumbSeo()
    {
        $manga = $this->manga;
        $breadcrumb = [];
        $position = 1;

        // Home
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => url('/')
        ]);

        // Origins (regions)
        foreach ($manga->origins as $origin) {
            array_push($breadcrumb, [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $origin->name,
                'item' => $origin->getUrl(),
            ]);
        }

        // Categories
        foreach ($manga->categories as $category) {
            array_push($breadcrumb, [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $category->name,
                'item' => $category->getUrl(),
            ]);
        }

        // Manga
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $manga->title,
            'item' => $manga->getUrl()
        ]);

        // Current chapter
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $this->getFormattedTitle(),
        ]);

        JsonLdMulti::newJsonLd()
            ->setType('BreadcrumbList')
            ->addValue('name', '')
            ->addValue('description', '')
            ->addValue('itemListElement', $breadcrumb);
    }

    /**
     * Increment view count with caching
     */
    public function incrementViewCount()
    {
        $cacheKey = "chapter_view_increment_{$this->id}";
        
        if (!Cache::has($cacheKey)) {
            $this->increment('view_count');
            $this->manga->increment('view_count');
            
            // Cache for 5 minutes to prevent spam
            Cache::put($cacheKey, true, 300);
        }
    }

    /**
     * Get SEO title for chapter
     */
    public function getSeoTitle()
    {
        $manga_title = $this->manga->title;
        $chapter_title = $this->getFormattedTitle();
        
        return "{$chapter_title} - {$manga_title} | " . setting('site_name', config('app.name'));
    }

    /**
     * Get SEO description for chapter
     */
    public function getSeoDescription()
    {
        $manga_title = $this->manga->title;
        $chapter_title = $this->getFormattedTitle();
        
        return "Read {$chapter_title} of {$manga_title} online. High quality manga reading experience with {$this->page_count} pages.";
    }

    /**
     * Get canonical URL for chapter
     */
    public function getCanonicalUrl()
    {
        return $this->getUrl();
    }

    /**
     * Get breadcrumbs array for chapter
     */
    public function getBreadcrumbs()
    {
        $manga = $this->manga;
        $breadcrumbs = [];

        // Home
        $breadcrumbs[] = [
            'name' => 'Home',
            'url' => url('/')
        ];

        // Manga
        $breadcrumbs[] = [
            'name' => $manga->title,
            'url' => $manga->getUrl()
        ];

        // Current chapter
        $breadcrumbs[] = [
            'name' => $this->getFormattedTitle(),
            'url' => null // Current page
        ];

        return $breadcrumbs;
    }

    /*
    |--------------------------------------------------------------------------
    | CACHING METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Cache chapter pages with optimization
     *
     * @param string $quality
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCachedPages(string $quality = 'medium')
    {
        $cacheKey = "chapter_pages:{$this->id}:quality_{$quality}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 10 * 60), function () use ($quality) {
            return $this->pages()
                ->orderBy('page_number', 'asc')
                ->get()
                ->map(function ($page) use ($quality) {
                    return [
                        'id' => $page->id,
                        'page_number' => $page->page_number,
                        'image_url' => $page->getOptimizedUrl($quality),
                        'thumbnail_url' => $page->getThumbnailUrl(),
                        'webp_url' => $page->getWebPUrl($quality),
                        'dimensions' => $page->getDimensions()
                    ];
                });
        });
    }

    /**
     * Cache chapter navigation data
     *
     * @return array
     */
    public function getCachedNavigation(): array
    {
        $cacheKey = "chapter_navigation:{$this->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 10 * 60), function () {
            $manga = $this->manga;
            
            return [
                'previous_chapter' => $this->getPreviousChapter(),
                'next_chapter' => $this->getNextChapter(),
                'chapter_list' => $manga->chapters()
                    ->select(['id', 'title', 'chapter_number', 'volume_number', 'published_at'])
                    ->orderBy('chapter_number', 'asc')
                    ->get()
                    ->map(function ($ch) {
                        return [
                            'id' => $ch->id,
                            'title' => $ch->title,
                            'chapter_number' => $ch->chapter_number,
                            'volume_number' => $ch->volume_number,
                            'published_at' => optional($ch->published_at)->toISOString(),
                            'is_current' => $ch->id === $this->id,
                            'url' => $ch->getUrl()
                        ];
                    })
            ];
        });
    }

    /**
     * Cache chapter with preloaded data for reading
     *
     * @param string $quality
     * @param int $preloadPages
     * @return array
     */
    public function getCachedReadingData(string $quality = 'medium', int $preloadPages = 3): array
    {
        $cacheKey = "chapter_reading_data:{$this->id}:quality_{$quality}:preload_{$preloadPages}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 15 * 60), function () use ($quality, $preloadPages) {
            $pages = $this->getCachedPages($quality);
            $navigation = $this->getCachedNavigation();
            
            // Preload next chapter pages if available
            $nextChapterPages = null;
            if ($navigation['next_chapter']) {
                $nextChapter = Chapter::find($navigation['next_chapter']->id);
                if ($nextChapter) {
                    $nextChapterPages = $nextChapter->pages()
                        ->orderBy('page_number', 'asc')
                        ->limit($preloadPages)
                        ->get()
                        ->map(function ($page) use ($quality) {
                            return [
                                'page_number' => $page->page_number,
                                'image_url' => $page->getOptimizedUrl($quality),
                                'webp_url' => $page->getWebPUrl($quality)
                            ];
                        });
                }
            }
            
            return [
                'chapter' => [
                    'id' => $this->id,
                    'title' => $this->title,
                    'chapter_number' => $this->chapter_number,
                    'page_count' => $this->page_count,
                    'manga' => [
                        'id' => $this->manga->id,
                        'title' => $this->manga->title,
                        'reading_direction' => $this->manga->reading_direction
                    ]
                ],
                'pages' => $pages,
                'navigation' => $navigation,
                'preload' => [
                    'next_chapter_pages' => $nextChapterPages
                ]
            ];
        });
    }

    /**
     * Invalidate all caches related to this chapter
     *
     * @return void
     */
    public function invalidateCache(): void
    {
        $patterns = [
            "chapter_pages:{$this->id}:*",
            "chapter_navigation:{$this->id}",
            "chapter_reading_data:{$this->id}:*",
            "next_chapter_{$this->id}",
            "prev_chapter_{$this->id}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
        
        // Also invalidate manga caches since chapter data affects manga
        $this->manga->invalidateCache();
    }

    /**
     * Cache chapter statistics
     *
     * @return array
     */
    public function getCachedStatistics(): array
    {
        $cacheKey = "chapter_statistics:{$this->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 10 * 60), function () {
            return [
                'page_count' => $this->page_count,
                'view_count' => $this->view_count,
                'published_at' => $this->published_at,
                'is_premium' => $this->is_premium,
                'reading_progress_count' => $this->readingProgress()->count(),
                'completion_rate' => $this->readingProgress()->whereNotNull('completed_at')->count()
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Relationship to Manga
     */
    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }

    /**
     * Relationship to Volume
     */
    public function volume()
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * Relationship to Pages
     */
    public function pages()
    {
        return $this->hasMany(Page::class)->orderBy('page_number');
    }

    /**
     * Relationship to ReadingProgress
     */
    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for published chapters
     */
    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    /**
     * Scope for premium chapters
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope for free chapters
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope to order by chapter number
     */
    public function scopeOrderByChapter($query, $direction = 'asc')
    {
        return $query->orderBy('chapter_number', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted chapter number
     */
    public function getFormattedChapterNumberAttribute()
    {
        return number_format($this->chapter_number, 1);
    }

    /**
     * Check if chapter is published
     */
    public function getIsPublishedAttribute()
    {
        return $this->published_at && $this->published_at <= now();
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-generate slug from title or chapter number
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        
        if (!$this->slug) {
            $this->attributes['slug'] = $value ? Str::slug($value) : "chapter-{$this->chapter_number}";
        }
    }

    /**
     * Ensure chapter number is properly formatted
     */
    public function setChapterNumberAttribute($value)
    {
        $this->attributes['chapter_number'] = number_format((float)$value, 1, '.', '');
    }
}