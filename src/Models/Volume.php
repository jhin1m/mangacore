<?php

namespace Ophim\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Contracts\HasUrlInterface;
use Backpack\Settings\app\Models\Setting;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;

class Volume extends Model implements Cacheable, HasUrlInterface
{
    use HasFactory;
    use HasCache;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'volumes';
    protected $guarded = ['id'];

    protected $fillable = [
        'manga_id',
        'volume_number',
        'title',
        'published_at',
        'chapter_count'
    ];

    protected $casts = [
        'volume_number' => 'integer',
        'chapter_count' => 'integer',
        'published_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate URL for volume page
     */
    public function getUrl()
    {
        $manga = Cache::remember("cache_manga_by_id:" . $this->manga_id, setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga;
        });

        $params = [];
        $site_routes_volume = setting('site_routes_volume', '/manga/{manga}/volume-{volume}');
        
        if (strpos($site_routes_volume, '{manga}')) $params['manga'] = $manga->slug;
        if (strpos($site_routes_volume, '{manga_id}')) $params['manga_id'] = $manga->id;
        if (strpos($site_routes_volume, '{volume}')) $params['volume'] = $this->volume_number;
        if (strpos($site_routes_volume, '{id}')) $params['id'] = $this->id;

        return route('volumes.show', $params);
    }

    /**
     * Get formatted volume title
     */
    public function getFormattedTitle()
    {
        if ($this->title) {
            return "Volume {$this->volume_number}: {$this->title}";
        }
        return "Volume {$this->volume_number}";
    }

    /**
     * Get volume display name for admin
     */
    public function getDisplayName()
    {
        return $this->getFormattedTitle();
    }

    /**
     * Admin button to view volume
     */
    public function openVolume($crud = false)
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="' . $this->getUrl() . '" data-toggle="tooltip" title="View volume"><i class="fa fa-book"></i> View</a>';
    }

    /**
     * Get next volume in sequence
     */
    public function getNextVolume()
    {
        return Cache::remember("next_volume_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga->volumes()
                ->where('volume_number', '>', $this->volume_number)
                ->where('published_at', '<=', now())
                ->orderBy('volume_number', 'asc')
                ->first();
        });
    }

    /**
     * Get previous volume in sequence
     */
    public function getPreviousVolume()
    {
        return Cache::remember("prev_volume_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->manga->volumes()
                ->where('volume_number', '<', $this->volume_number)
                ->where('published_at', '<=', now())
                ->orderBy('volume_number', 'desc')
                ->first();
        });
    }

    /**
     * Get first chapter of this volume
     */
    public function getFirstChapter()
    {
        return Cache::remember("first_chapter_volume_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->chapters()
                ->where('published_at', '<=', now())
                ->orderBy('chapter_number', 'asc')
                ->first();
        });
    }

    /**
     * Get last chapter of this volume
     */
    public function getLastChapter()
    {
        return Cache::remember("last_chapter_volume_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->chapters()
                ->where('published_at', '<=', now())
                ->orderBy('chapter_number', 'desc')
                ->first();
        });
    }

    /**
     * Calculate total pages in this volume
     */
    public function getTotalPages()
    {
        return Cache::remember("total_pages_volume_{$this->id}", setting('site_cache_ttl', 5 * 60), function () {
            return $this->chapters()->sum('page_count');
        });
    }

    /**
     * Get reading progress for this volume
     */
    public function getReadingProgress($user_id = null)
    {
        if (!$user_id) {
            return 0;
        }

        $total_chapters = $this->chapters()->count();
        if ($total_chapters === 0) {
            return 0;
        }

        $read_chapters = $this->chapters()
            ->whereHas('readingProgress', function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->whereNotNull('completed_at');
            })
            ->count();

        return round(($read_chapters / $total_chapters) * 100, 1);
    }

    /**
     * Update chapter count based on actual chapters
     */
    public function updateChapterCount()
    {
        $count = $this->chapters()->count();
        $this->update(['chapter_count' => $count]);
        return $count;
    }

    /**
     * Check if volume is published
     */
    public function isPublished()
    {
        return $this->published_at && $this->published_at <= now();
    }

    /**
     * Get volume status for display
     */
    public function getStatus()
    {
        if (!$this->isPublished()) {
            return 'Upcoming';
        }

        $published_chapters = $this->chapters()->where('published_at', '<=', now())->count();
        $total_chapters = $this->chapter_count;

        if ($total_chapters > 0 && $published_chapters >= $total_chapters) {
            return 'Complete';
        }

        return 'Ongoing';
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
     * Relationship to Chapters
     */
    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for published volumes
     */
    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    /**
     * Scope to order by volume number
     */
    public function scopeOrderByVolume($query, $direction = 'asc')
    {
        return $query->orderBy('volume_number', $direction);
    }

    /**
     * Scope to get volumes with chapters
     */
    public function scopeWithChapters($query)
    {
        return $query->has('chapters');
    }

    /**
     * Scope to get volumes by manga
     */
    public function scopeByManga($query, $manga_id)
    {
        return $query->where('manga_id', $manga_id);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted volume number
     */
    public function getFormattedVolumeNumberAttribute()
    {
        return str_pad($this->volume_number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Check if volume is published
     */
    public function getIsPublishedAttribute()
    {
        return $this->published_at && $this->published_at <= now();
    }

    /**
     * Get volume completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->chapter_count <= 0) {
            return 0;
        }

        $published_chapters = $this->chapters()->where('published_at', '<=', now())->count();
        return round(($published_chapters / $this->chapter_count) * 100, 1);
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure volume number is positive integer
     */
    public function setVolumeNumberAttribute($value)
    {
        $this->attributes['volume_number'] = max(1, (int)$value);
    }

    /**
     * Auto-update chapter count when saving
     */
    public function save(array $options = [])
    {
        $result = parent::save($options);
        
        // Update chapter count if this is an existing volume
        if ($this->exists && !$this->wasRecentlyCreated) {
            $this->updateChapterCount();
        }
        
        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Validation rules for volume creation/update
     */
    public static function validationRules()
    {
        return [
            'manga_id' => 'required|exists:mangas,id',
            'volume_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'chapter_count' => 'integer|min:0'
        ];
    }

    /**
     * Validate volume numbering within manga
     */
    public function validateVolumeNumbering()
    {
        // Check for duplicate volume numbers in the same manga
        $duplicate = static::where('manga_id', $this->manga_id)
            ->where('volume_number', $this->volume_number)
            ->where('id', '!=', $this->id)
            ->exists();
            
        return !$duplicate;
    }

    /**
     * Validate that published_at is not in the future if chapters exist
     */
    public function validatePublishedDate()
    {
        if ($this->published_at && $this->published_at > now()) {
            // Check if there are already published chapters
            $published_chapters = $this->chapters()
                ->where('published_at', '<=', now())
                ->count();
                
            if ($published_chapters > 0) {
                return false; // Volume can't be unpublished if chapters are published
            }
        }
        
        return true;
    }
}