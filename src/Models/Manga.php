<?php

namespace Ophim\Core\Models;

use Illuminate\Support\Str;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Ophim\Core\Contracts\TaxonomyInterface;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Contracts\SeoInterface;
use Ophim\Core\Traits\ActorLog;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Traits\HasTitle;
use Ophim\Core\Traits\Sluggable;

class Manga extends Model implements TaxonomyInterface, Cacheable, SeoInterface
{
    use CrudTrait;
    use ActorLog;
    use Sluggable;
    use HasFactory;
    use HasCache;
    use HasTitle;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'mangas';
    protected $guarded = ['id'];

    protected $fillable = [
        'title', 'slug', 'original_title', 'other_name', 'description', 'cover_image', 'banner_image',
        'type', 'status', 'publication_year', 'total_chapters', 'total_volumes', 
        'reading_direction', 'demographic', 'rating', 'rating_count', 'view_count', 'view_day', 'view_week', 'view_month',
        'is_completed', 'is_recommended', 'is_adult_content', 'user_id', 'user_name'
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'total_chapters' => 'integer',
        'total_volumes' => 'integer',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'view_count' => 'integer',
        'view_day' => 'integer',
        'view_week' => 'integer',
        'view_month' => 'integer',
        'is_completed' => 'boolean',
        'is_recommended' => 'boolean',
        'is_adult_content' => 'boolean',
    ];

    protected $attributes = [
        'rating' => 0,
        'rating_count' => 0,
        'view_count' => 0,
        'view_day' => 0,
        'view_week' => 0,
        'view_month' => 0,
        'is_completed' => false,
        'is_recommended' => false,
        'is_adult_content' => false,
        'type' => 'manga',
        'status' => 'ongoing',
        'demographic' => 'general',
        'reading_direction' => 'ltr',
    ];

    // Constants for validation
    const TYPES = ['manga', 'manhwa', 'manhua', 'webtoon'];
    const STATUSES = ['ongoing', 'completed', 'hiatus', 'cancelled'];
    const DEMOGRAPHICS = ['shounen', 'seinen', 'josei', 'shoujo', 'kodomomuke', 'general'];
    const READING_DIRECTIONS = ['ltr', 'rtl', 'vertical'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            // Ensure rating is never null
            if ($instance->rating === null) {
                $instance->rating = 0;
            }
        });

        static::updating(function ($instance) {
            $instance->timestamps = request('timestamps') ?: false;
            
            // Ensure rating is never null
            if ($instance->rating === null) {
                $instance->rating = 0;
            }
        });

        // Invalidate caches when manga is updated or deleted
        static::updated(function ($instance) {
            $instance->invalidateCache();
        });

        static::deleted(function ($instance) {
            $instance->invalidateCache();
        });
    }

    public static function primaryCacheKey(): string
    {
        $site_routes = setting('site_routes_manga', '/manga/{manga}');
        if (strpos($site_routes, '{manga}')) return 'slug';
        if (strpos($site_routes, '{id}')) return 'id';
        return 'slug';
    }

    public function getUrl()
    {
        $params = [];
        $site_routes = setting('site_routes_manga', '/manga/{manga}');
        if (strpos($site_routes, '{manga}')) $params['manga'] = $this->slug;
        if (strpos($site_routes, '{id}')) $params['id'] = $this->id;
        return route('manga.show', $params);
    }

    public function getCoverUrl()
    {
        if (setting('site_image_proxy_enable', false)) {
            $image_url = filter_var($this->cover_image, FILTER_VALIDATE_URL) ? $this->cover_image : url('/') . $this->cover_image;
            return str_replace('{image_url}', urlencode($image_url), setting('site_image_proxy_url', ''));
        }
        return $this->cover_image;
    }

    public function getBannerUrl()
    {
        if ($this->banner_image) {
            if (setting('site_image_proxy_enable', false)) {
                $image_url = filter_var($this->banner_image, FILTER_VALIDATE_URL) ? $this->banner_image : url('/') . $this->banner_image;
                return str_replace('{image_url}', urlencode($image_url), setting('site_image_proxy_url', ''));
            }
            return $this->banner_image;
        }
        return $this->getCoverUrl();
    }

    public function getRating()
    {
        return number_format($this->rating > 0 ? $this->rating : 8.0, 1);
    }

    public function getRatingCount()
    {
        return $this->rating_count >= 1 ? $this->rating_count : 1;
    }

    public function generateSeoTags()
    {
        $manga_cover_url = filter_var($this->cover_image, FILTER_VALIDATE_URL) ? $this->cover_image : request()->root() . $this->cover_image;
        $manga_banner_url = filter_var($this->banner_image, FILTER_VALIDATE_URL) ? $this->banner_image : request()->root() . $this->banner_image;
        $seo_des = Str::limit(strip_tags($this->description), 150, '...');
        $getTitle = $this->getTitle();
        $getUrl = $this->getUrl();
        $site_meta_siteName = setting('site_meta_siteName');

        SEOMeta::setTitle($getTitle, false)
            ->setDescription($seo_des)
            ->addKeyword($this->tags()->pluck('name')->toArray())
            ->addMeta('article:published_time', $this->updated_at->toW3CString(), 'property')
            ->addMeta('article:section', $this->categories->pluck('name')->join(","), 'property')
            ->addMeta('article:tag', $this->tags->pluck('name')->join(","), 'property')
            ->setCanonical($getUrl)
            ->setPrev(url('/'))
            ->setPrev(url('/'));

        // Enhanced Open Graph for manga/comic content
        OpenGraph::setType('book')
            ->setTitle($getTitle, false)
            ->setDescription($seo_des)
            ->setSiteName($site_meta_siteName)
            ->addProperty('locale', 'vi-VN')
            ->addProperty('updated_time', $this->updated_at->toW3CString())
            ->addProperty('url', $getUrl)
            ->addImages([$manga_cover_url, $manga_banner_url])
            ->addProperty('book:author', $this->authors->pluck('name')->join(", "))
            ->addProperty('book:isbn', '')
            ->addProperty('book:release_date', $this->created_at->toW3CString())
            ->addProperty('book:tag', $this->tags->pluck('name')->join(", "))
            ->addProperty('article:section', $this->categories->pluck('name')->join(", "))
            ->addProperty('article:tag', $this->tags->pluck('name')->join(", "))
            ->addProperty('og:type', 'book')
            ->addProperty('og:book:author', $this->authors->pluck('name')->join(", "))
            ->addProperty('og:book:genre', $this->categories->pluck('name')->join(", "));

        // Enhanced Twitter Card for manga content
        TwitterCard::setSite($site_meta_siteName)
            ->setTitle($getTitle, false)
            ->setType('summary_large_image')
            ->setImage($manga_cover_url)
            ->setDescription($seo_des)
            ->setUrl($getUrl)
            ->addValue('twitter:label1', 'Status')
            ->addValue('twitter:data1', $this->getStatus())
            ->addValue('twitter:label2', 'Type')
            ->addValue('twitter:data2', $this->getType());

        // Enhanced Schema.org Book/Comic markup
        JsonLdMulti::newJsonLd()
            ->setSite($site_meta_siteName)
            ->addValue('@context', 'https://schema.org')
            ->addValue('@type', 'Book')
            ->addValue('bookFormat', 'https://schema.org/GraphicNovel')
            ->addValue('name', $getTitle)
            ->addValue('alternateName', $this->original_title)
            ->addValue('description', $seo_des)
            ->addValue('url', $getUrl)
            ->addValue('image', [$manga_cover_url, $manga_banner_url])
            ->addValue('dateCreated', $this->created_at->toW3CString())
            ->addValue('dateModified', $this->updated_at->toW3CString())
            ->addValue('datePublished', $this->created_at->toW3CString())
            ->addValue('inLanguage', 'vi-VN')
            ->addValue('workExample', [
                '@type' => 'Book',
                'bookFormat' => 'https://schema.org/GraphicNovel',
                'abridged' => false
            ])
            ->addValue('genre', $this->categories->pluck('name')->toArray())
            ->addValue('keywords', $this->tags->pluck('name')->join(', '))
            ->addValue('numberOfPages', $this->total_chapters)
            ->addValue('bookEdition', $this->type)
            ->addValue('copyrightYear', $this->publication_year)
            ->addValue('author', count($this->authors) ? $this->authors->map(function ($author) {
                return [
                    '@type' => 'Person', 
                    'name' => $author->name,
                    'url' => $author->getUrl()
                ];
            })->toArray() : [])
            ->addValue('illustrator', count($this->artists) ? $this->artists->map(function ($artist) {
                return [
                    '@type' => 'Person', 
                    'name' => $artist->name,
                    'url' => $artist->getUrl()
                ];
            })->toArray() : [])
            ->addValue('publisher', count($this->publishers) ? $this->publishers->map(function ($publisher) {
                return [
                    '@type' => 'Organization', 
                    'name' => $publisher->name,
                    'url' => $publisher->getUrl()
                ];
            })->toArray() : [])
            ->addValue('aggregateRating', [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->getRating(),
                'bestRating' => 10,
                'worstRating' => 1,
                'reviewCount' => $this->getRatingCount(),
                'ratingCount' => $this->getRatingCount()
            ])
            ->addValue('offers', [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock',
                'url' => $getUrl
            ]);

        // Breadcrumb navigation
        $breadcrumb = [];
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/')
        ]);
        foreach ($this->origins as $item) {
            array_push($breadcrumb, [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $item->name,
                'item' => $item->getUrl(),
            ]);
        }
        foreach ($this->categories as $item) {
            array_push($breadcrumb, [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $item->name,
                'item' => $item->getUrl(),
            ]);
        }
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $this->title
        ]);

        JsonLdMulti::newJsonLd()
            ->setType('BreadcrumbList')
            ->addValue('name', '')
            ->addValue('description', '')
            ->addValue('itemListElement', $breadcrumb);
    }

    /**
     * Get canonical URL for manga
     */
    public function getCanonicalUrl()
    {
        return $this->getUrl();
    }

    public function openView($crud = false)
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="' . $this->getUrl() . '" data-toggle="tooltip" title="View link"><i class="la la-link"></i> View</a>';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_manga');
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'artist_manga');
    }

    public function publishers()
    {
        return $this->belongsToMany(Publisher::class, 'manga_publisher');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_manga');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'manga_tag');
    }

    public function origins()
    {
        return $this->belongsToMany(Origin::class, 'manga_origin');
    }

    public function volumes()
    {
        return $this->hasMany(Volume::class)->orderBy('volume_number');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function ratedByUsers()
    {
        return $this->belongsToMany(User::class, 'manga_user')
            ->withPivot(['rating', 'review', 'is_favorite'])
            ->withTimestamps();
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'manga_user')
            ->wherePivot('is_favorite', true)
            ->withPivot(['rating', 'review', 'is_favorite'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDemographic($query, $demographic)
    {
        return $query->where('demographic', $demographic);
    }

    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatus()
    {
        $statuses = [
            'ongoing' => __('Đang cập nhật'),
            'completed' => __('Hoàn thành'),
            'hiatus' => __('Tạm dừng'),
            'cancelled' => __('Đã hủy')
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getType()
    {
        $types = [
            'manga' => __('Manga'),
            'manhwa' => __('Manhwa'),
            'manhua' => __('Manhua'),
            'webtoon' => __('Webtoon')
        ];
        return $types[$this->type] ?? $this->type;
    }

    public function getDemographic()
    {
        $demographics = [
            'shounen' => __('Shounen'),
            'seinen' => __('Seinen'),
            'josei' => __('Josei'),
            'shoujo' => __('Shoujo'),
            'kodomomuke' => __('Kodomomuke'),
            'general' => __('Tổng hợp')
        ];
        return $demographics[$this->demographic] ?? $this->demographic;
    }

    public function getReadingDirection()
    {
        $directions = [
            'ltr' => __('Trái sang phải'),
            'rtl' => __('Phải sang trái'),
            'vertical' => __('Dọc')
        ];
        return $directions[$this->reading_direction] ?? $this->reading_direction;
    }

    protected function titlePattern(): string
    {
        return Setting::get('site_manga_title', '{title}');
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setOtherNameAttribute($value)
    {
        $this->attributes['other_name'] = is_array($value) ? implode(', ', $value) : $value;
    }

    public function getOtherNameAttribute($value)
    {
        return $value ? explode(', ', $value) : [];
    }

    /**
     * Ensure rating is never null
     */
    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = $value === null || $value === '' ? 0 : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | CACHING METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Cache manga with relationships for faster access
     *
     * @param array $relationships
     * @return self
     */
    public function cacheWithRelationships(array $relationships = []): self
    {
        $defaultRelationships = ['authors', 'artists', 'categories', 'tags', 'origins'];
        $relationships = array_merge($defaultRelationships, $relationships);
        
        $cacheKey = "manga_with_relationships:{$this->id}:" . md5(implode(',', $relationships));
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($relationships) {
            return $this->load($relationships);
        });
    }

    /**
     * Cache manga chapters with pagination
     *
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCachedChapters(int $page = 1, int $perPage = 20)
    {
        $cacheKey = "manga_chapters:{$this->id}:page_{$page}:per_{$perPage}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($perPage) {
            return $this->chapters()
                ->with(['volume'])
                ->orderBy('chapter_number', 'asc')
                ->paginate($perPage);
        });
    }

    /**
     * Cache manga statistics
     *
     * @return array
     */
    public function getCachedStatistics(): array
    {
        $cacheKey = "manga_statistics:{$this->id}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () {
            return [
                'total_chapters' => $this->chapters()->count(),
                'total_volumes' => $this->volumes()->count(),
                'total_pages' => $this->chapters()->withCount('pages')->get()->sum('pages_count'),
                'latest_chapter' => optional($this->chapters()->orderBy('chapter_number', 'desc')->first())->chapter_number,
                'last_updated' => $this->updated_at,
                'average_rating' => $this->getAverageUserRating(),
                'rating_count' => $this->getUserRatingCount(),
                'favorite_count' => $this->getFavoriteCount()
            ];
        });
    }

    /**
     * Cache related manga recommendations
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCachedRelatedManga(int $limit = 10)
    {
        $cacheKey = "manga_related:{$this->id}:limit_{$limit}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 10 * 60), function () use ($limit) {
            return Manga::where('id', '!=', $this->id)
                ->where(function ($query) {
                    // Same categories
                    $query->whereHas('categories', function ($q) {
                        $q->whereIn('categories.id', $this->categories->pluck('id'));
                    })
                    // Same authors
                    ->orWhereHas('authors', function ($q) {
                        $q->whereIn('authors.id', $this->authors->pluck('id'));
                    })
                    // Same tags
                    ->orWhereHas('tags', function ($q) {
                        $q->whereIn('tags.id', $this->tags->pluck('id'));
                    });
                })
                ->with(['authors', 'artists', 'categories'])
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Invalidate all caches related to this manga
     *
     * @return void
     */
    public function invalidateCache(): void
    {
        $patterns = [
            "manga_with_relationships:{$this->id}:*",
            "manga_chapters:{$this->id}:*",
            "manga_statistics:{$this->id}",
            "manga_related:{$this->id}:*",
            "cache_manga_by_id:{$this->id}",
            "cache_manga_by_slug:{$this->slug}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Cache popular manga
     *
     * @param int $limit
     * @param string $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedPopular(int $limit = 20, string $period = 'all'): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "manga_popular:{$period}:limit_{$limit}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 15 * 60), function () use ($limit, $period) {
            $query = static::with(['authors', 'artists', 'categories']);
            
            switch ($period) {
                case 'day':
                    $query->orderBy('view_day', 'desc');
                    break;
                case 'week':
                    $query->orderBy('view_week', 'desc');
                    break;
                case 'month':
                    $query->orderBy('view_month', 'desc');
                    break;
                default:
                    $query->orderBy('view_count', 'desc');
            }
            
            return $query->limit($limit)->get();
        });
    }

    /**
     * Cache latest manga
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedLatest(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "manga_latest:limit_{$limit}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 5 * 60), function () use ($limit) {
            return static::with(['authors', 'artists', 'categories'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache featured manga
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedFeatured(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "manga_featured:limit_{$limit}";
        
        return Cache::remember($cacheKey, setting('site_cache_ttl', 30 * 60), function () use ($limit) {
            return static::with(['authors', 'artists', 'categories'])
                ->where('is_recommended', true)
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RATING & FAVORITE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get average user rating for this manga.
     *
     * @return float|null
     */
    public function getAverageUserRating(): ?float
    {
        $average = $this->ratedByUsers()->avg('manga_user.rating');
        return $average ? round($average, 1) : null;
    }

    /**
     * Get total number of user ratings.
     *
     * @return int
     */
    public function getUserRatingCount(): int
    {
        return $this->ratedByUsers()->whereNotNull('manga_user.rating')->count();
    }

    /**
     * Get total number of users who favorited this manga.
     *
     * @return int
     */
    public function getFavoriteCount(): int
    {
        return $this->favoritedByUsers()->count();
    }

    /**
     * Check if a user has rated this manga.
     *
     * @param int $userId
     * @return bool
     */
    public function isRatedByUser(int $userId): bool
    {
        return $this->ratedByUsers()
            ->where('user_id', $userId)
            ->whereNotNull('manga_user.rating')
            ->exists();
    }

    /**
     * Check if a user has favorited this manga.
     *
     * @param int $userId
     * @return bool
     */
    public function isFavoritedByUser(int $userId): bool
    {
        return $this->favoritedByUsers()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's rating for this manga.
     *
     * @param int $userId
     * @return float|null
     */
    public function getUserRating(int $userId): ?float
    {
        $pivot = $this->ratedByUsers()->where('user_id', $userId)->first();
        return $pivot ? $pivot->pivot->rating : null;
    }

    /**
     * Update the manga's overall rating based on user ratings.
     *
     * @return void
     */
    public function updateOverallRating(): void
    {
        $userRating = $this->getAverageUserRating();
        $userCount = $this->getUserRatingCount();
        
        // If we have user ratings, use them; otherwise keep the existing rating
        if ($userRating && $userCount > 0) {
            $this->update([
                'rating' => $userRating,
                'rating_count' => $userCount
            ]);
        }
    }
}