<?php

namespace Ophim\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ophim\Core\Traits\HasFactory;
use Backpack\Settings\app\Models\Setting;

class Page extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'pages';
    protected $guarded = ['id'];

    protected $fillable = [
        'chapter_id',
        'page_number',
        'image_url'
    ];

    protected $casts = [
        'page_number' => 'integer'
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get optimized image URL based on quality setting
     *
     * @param string $quality Quality level: 'low', 'medium', 'high'
     * @return string
     */
    public function getOptimizedUrl($quality = 'medium')
    {
        // If image proxy is enabled, use it for optimization
        if (setting('site_image_proxy_enable', false)) {
            $image_url = filter_var($this->image_url, FILTER_VALIDATE_URL) 
                ? $this->image_url 
                : url('/') . $this->image_url;
            
            $proxy_url = setting('site_image_proxy_url', '');
            
            // Add quality parameter to proxy URL if supported
            $quality_params = [
                'low' => '&quality=60&width=800',
                'medium' => '&quality=80&width=1200', 
                'high' => '&quality=95&width=1600'
            ];
            
            $quality_param = $quality_params[$quality] ?? $quality_params['medium'];
            $proxy_url_with_quality = $proxy_url . $quality_param;
            
            return str_replace('{image_url}', urlencode($image_url), $proxy_url_with_quality);
        }

        return $this->image_url;
    }

    /**
     * Get thumbnail URL for page preview
     *
     * @return string
     */
    public function getThumbnailUrl()
    {
        return $this->getOptimizedUrl('low');
    }

    /**
     * Get WebP version URL if available
     *
     * @param string $quality Quality level for WebP
     * @return string
     */
    public function getWebPUrl($quality = 'medium')
    {
        // Check if WebP version exists
        $webp_url = $this->getWebPPath();
        
        if ($webp_url && $this->fileExists($webp_url)) {
            return $webp_url;
        }
        
        // Fallback to original image
        return $this->image_url;
    }

    /**
     * Generate WebP path from original image URL
     *
     * @return string
     */
    protected function getWebPPath()
    {
        $path_info = pathinfo($this->image_url);
        
        if (isset($path_info['dirname']) && isset($path_info['filename'])) {
            return $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        }
        
        return null;
    }

    /**
     * Check if file exists (works for both local and remote URLs)
     *
     * @param string $url
     * @return bool
     */
    protected function fileExists($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // For remote URLs, we'll assume they exist to avoid performance issues
            // In production, you might want to implement a more sophisticated check
            return true;
        }
        
        // For local files
        return Storage::exists(ltrim($url, '/'));
    }

    /**
     * Get image dimensions if available
     *
     * @return array|null
     */
    public function getImageDimensions()
    {
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            // For remote images, we can't easily get dimensions without downloading
            return null;
        }
        
        $local_path = storage_path('app/public/' . ltrim($this->image_url, '/'));
        
        if (file_exists($local_path)) {
            $size = getimagesize($local_path);
            return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
        }
        
        return null;
    }

    /**
     * Alias for getImageDimensions for API consistency
     *
     * @return array|null
     */
    public function getDimensions()
    {
        return $this->getImageDimensions();
    }

    /**
     * Get file size in bytes
     *
     * @return int|null
     */
    public function getFileSize()
    {
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return null; // Can't determine remote file size easily
        }
        
        $local_path = storage_path('app/public/' . ltrim($this->image_url, '/'));
        
        return file_exists($local_path) ? filesize($local_path) : null;
    }

    /**
     * Get human readable file size
     *
     * @return string|null
     */
    public function getHumanFileSize()
    {
        $size = $this->getFileSize();
        
        if (!$size) {
            return null;
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit_index = 0;
        
        while ($size >= 1024 && $unit_index < count($units) - 1) {
            $size /= 1024;
            $unit_index++;
        }
        
        return round($size, 2) . ' ' . $units[$unit_index];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Relationship to Chapter
     */
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to order pages by page number
     */
    public function scopeOrderByPage($query, $direction = 'asc')
    {
        return $query->orderBy('page_number', $direction);
    }

    /**
     * Scope to get pages in a specific range
     */
    public function scopeInRange($query, $start, $end)
    {
        return $query->whereBetween('page_number', [$start, $end]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted page number (e.g., "Page 01")
     */
    public function getFormattedPageNumberAttribute()
    {
        return 'Page ' . str_pad($this->page_number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Check if this is the first page
     */
    public function getIsFirstPageAttribute()
    {
        return $this->page_number === 1;
    }

    /**
     * Check if this is the last page
     */
    public function getIsLastPageAttribute()
    {
        return $this->page_number === $this->chapter->page_count;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure page number is positive integer
     */
    public function setPageNumberAttribute($value)
    {
        $this->attributes['page_number'] = max(1, (int)$value);
    }

    /**
     * Clean and validate image URL
     */
    public function setImageUrlAttribute($value)
    {
        // Remove any extra whitespace
        $value = trim($value);
        
        // Ensure URL is properly formatted
        if (!filter_var($value, FILTER_VALIDATE_URL) && !Str::startsWith($value, '/')) {
            $value = '/' . ltrim($value, '/');
        }
        
        $this->attributes['image_url'] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Validation rules for page creation/update
     */
    public static function validationRules()
    {
        return [
            'chapter_id' => 'required|exists:chapters,id',
            'page_number' => 'required|integer|min:1',
            'image_url' => 'required|string|max:500'
        ];
    }

    /**
     * Validate image URL format and accessibility
     */
    public function validateImageUrl()
    {
        $url = $this->image_url;
        
        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL) && !Str::startsWith($url, '/')) {
            return false;
        }
        
        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowed_extensions)) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate page numbering within chapter
     */
    public function validatePageNumbering()
    {
        // Check for duplicate page numbers in the same chapter
        $duplicate = static::where('chapter_id', $this->chapter_id)
            ->where('page_number', $this->page_number)
            ->where('id', '!=', $this->id)
            ->exists();
            
        return !$duplicate;
    }
}