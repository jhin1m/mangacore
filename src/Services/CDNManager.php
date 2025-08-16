<?php

namespace Ophim\Core\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ophim\Core\Exceptions\ImageProcessingException;

class CDNManager
{
    /**
     * CDN configuration
     */
    protected $cdnConfig;

    /**
     * CDN disk instance
     */
    protected $cdnDisk;

    public function __construct()
    {
        $this->cdnConfig = Config::get('ophim.cdn', []);
        
        // Initialize CDN disk if configured
        if ($this->isCdnEnabled()) {
            $this->cdnDisk = Storage::disk($this->cdnConfig['disk'] ?? 'public');
        }
    }

    /**
     * Check if CDN is enabled
     *
     * @return bool
     */
    public function isCdnEnabled()
    {
        return !empty($this->cdnConfig['enabled']) && $this->cdnConfig['enabled'] === true;
    }

    /**
     * Upload image to CDN
     *
     * @param string $localPath Local file path
     * @param string $cdnPath CDN destination path
     * @param string $type Image type (page, thumbnail, cover, etc.)
     * @return string CDN URL
     */
    public function uploadImage($localPath, $cdnPath, $type = 'page')
    {
        if (!$this->isCdnEnabled()) {
            return Storage::url($localPath);
        }

        try {
            // Read local file content
            if (!Storage::exists($localPath)) {
                throw ImageProcessingException::fileNotFound($localPath);
            }

            $fileContent = Storage::get($localPath);
            
            // Upload to CDN
            $success = $this->cdnDisk->put($cdnPath, $fileContent);
            
            if (!$success) {
                throw ImageProcessingException::cdnUploadFailed("Failed to upload {$localPath} to CDN");
            }

            // Return CDN URL
            return $this->getCdnUrl($cdnPath);
            
        } catch (\Exception $e) {
            Log::error("CDN upload failed for {$localPath}: " . $e->getMessage());
            
            // Fallback to local storage URL
            return Storage::url($localPath);
        }
    }

    /**
     * Get CDN URL for a path
     *
     * @param string $path
     * @return string
     */
    public function getCdnUrl($path)
    {
        if (!$this->isCdnEnabled()) {
            return Storage::url($path);
        }

        $baseUrl = $this->cdnConfig['base_url'] ?? '';
        
        if (empty($baseUrl)) {
            return $this->cdnDisk->url($path);
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get optimized URL with quality parameter and caching headers
     *
     * @param string $imageUrl Base image URL
     * @param string $quality Quality level (low, medium, high)
     * @param array $options Additional options (cache_ttl, format, etc.)
     * @return string
     */
    public function getOptimizedUrl($imageUrl, $quality = 'medium', array $options = [])
    {
        if (!$this->isCdnEnabled()) {
            return $this->addCacheHeaders($imageUrl, $options);
        }

        // If CDN supports on-the-fly optimization, add quality parameters
        if (!empty($this->cdnConfig['supports_optimization'])) {
            $qualityParams = $this->getQualityParams($quality, $options);
            
            if (!empty($qualityParams)) {
                $separator = strpos($imageUrl, '?') !== false ? '&' : '?';
                return $imageUrl . $separator . $qualityParams;
            }
        }

        return $this->addCacheHeaders($imageUrl, $options);
    }

    /**
     * Add cache headers to image URL
     *
     * @param string $imageUrl
     * @param array $options
     * @return string
     */
    protected function addCacheHeaders($imageUrl, array $options = [])
    {
        $cacheTtl = $options['cache_ttl'] ?? 86400; // 24 hours default
        $version = $options['version'] ?? time();
        
        $separator = strpos($imageUrl, '?') !== false ? '&' : '?';
        return $imageUrl . $separator . "v={$version}&cache={$cacheTtl}";
    }

    /**
     * Get quality parameters for CDN optimization
     *
     * @param string $quality
     * @param array $options
     * @return string
     */
    protected function getQualityParams($quality, array $options = [])
    {
        $params = [];
        
        switch ($quality) {
            case 'low':
                $params['q'] = '60';
                $params['w'] = '800';
                $params['f'] = 'webp';
                break;
            case 'medium':
                $params['q'] = '80';
                $params['w'] = '1200';
                $params['f'] = 'webp';
                break;
            case 'high':
                $params['q'] = '95';
                $params['f'] = 'webp';
                break;
        }

        // Add format override if specified
        if (!empty($options['format'])) {
            $params['f'] = $options['format'];
        }

        // Add cache control
        $params['cache'] = $options['cache_ttl'] ?? 86400;
        
        // Add version for cache busting
        if (!empty($options['version'])) {
            $params['v'] = $options['version'];
        }

        return http_build_query($params);
    }

    /**
     * Get image with proper cache headers for direct serving
     *
     * @param string $imagePath
     * @param array $headers
     * @return \Illuminate\Http\Response
     */
    public function serveImageWithCache($imagePath, array $headers = [])
    {
        $defaultHeaders = [
            'Cache-Control' => 'public, max-age=86400, immutable',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($imagePath)) . ' GMT',
            'ETag' => '"' . md5_file($imagePath) . '"'
        ];

        $headers = array_merge($defaultHeaders, $headers);

        return response()->file($imagePath, $headers);
    }

    /**
     * Delete image from CDN
     *
     * @param string $cdnPath
     * @return bool
     */
    public function deleteImage($cdnPath)
    {
        if (!$this->isCdnEnabled()) {
            return true;
        }

        try {
            return $this->cdnDisk->delete($cdnPath);
        } catch (\Exception $e) {
            Log::error("Failed to delete CDN image {$cdnPath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch upload images to CDN
     *
     * @param array $images Array of ['local_path' => 'cdn_path'] pairs
     * @return array Results
     */
    public function batchUpload(array $images)
    {
        $results = [];
        
        foreach ($images as $localPath => $cdnPath) {
            try {
                $url = $this->uploadImage($localPath, $cdnPath);
                $results[] = [
                    'success' => true,
                    'local_path' => $localPath,
                    'cdn_path' => $cdnPath,
                    'url' => $url
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'local_path' => $localPath,
                    'cdn_path' => $cdnPath,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Generate responsive image URLs
     *
     * @param string $baseUrl
     * @param array $sizes
     * @return array
     */
    public function generateResponsiveUrls($baseUrl, array $sizes = [])
    {
        if (empty($sizes)) {
            $sizes = ['400w', '800w', '1200w', '1600w'];
        }

        $urls = [];
        
        foreach ($sizes as $size) {
            if ($this->isCdnEnabled() && !empty($this->cdnConfig['supports_optimization'])) {
                $width = (int) str_replace('w', '', $size);
                $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
                $urls[$size] = $baseUrl . $separator . "w={$width}";
            } else {
                $urls[$size] = $baseUrl;
            }
        }
        
        return $urls;
    }

    /**
     * Purge CDN cache for specific paths
     *
     * @param array $paths
     * @return bool
     */
    public function purgeCache(array $paths)
    {
        if (!$this->isCdnEnabled()) {
            return true;
        }

        // Implementation depends on CDN provider
        // This is a placeholder for CDN-specific cache purging
        
        try {
            // Log cache purge request
            Log::info("CDN cache purge requested for paths: " . implode(', ', $paths));
            
            // TODO: Implement actual CDN cache purging based on provider
            // Examples:
            // - CloudFlare: Use CloudFlare API
            // - AWS CloudFront: Use AWS SDK
            // - Custom CDN: Use provider-specific API
            
            return true;
        } catch (\Exception $e) {
            Log::error("CDN cache purge failed: " . $e->getMessage());
            return false;
        }
    }
}