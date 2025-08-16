<?php

namespace Ophim\Core\Traits;

use Illuminate\Support\Facades\Storage;
use Ophim\Core\Services\ImageProcessor;
use Ophim\Core\Services\CDNManager;

trait HasImages
{
    /**
     * Image processor instance
     */
    protected $imageProcessor;

    /**
     * CDN manager instance
     */
    protected $cdnManager;

    /**
     * Boot the trait
     */
    public static function bootHasImages()
    {
        static::deleting(function ($model) {
            $model->cleanupImages();
        });
    }

    /**
     * Get image processor instance
     */
    protected function getImageProcessor()
    {
        if (!$this->imageProcessor) {
            $this->imageProcessor = new ImageProcessor();
        }
        
        return $this->imageProcessor;
    }

    /**
     * Get CDN manager instance
     */
    protected function getCdnManager()
    {
        if (!$this->cdnManager) {
            $this->cdnManager = new CDNManager();
        }
        
        return $this->cdnManager;
    }

    /**
     * Get optimized image URL
     *
     * @param string $imageField
     * @param string $quality
     * @return string|null
     */
    public function getOptimizedImageUrl($imageField, $quality = 'medium')
    {
        $imageUrl = $this->getAttribute($imageField);
        
        if (!$imageUrl) {
            return null;
        }

        return $this->getCdnManager()->getOptimizedUrl($imageUrl, $quality);
    }

    /**
     * Get responsive image URLs
     *
     * @param string $imageField
     * @param array $sizes
     * @return array
     */
    public function getResponsiveImageUrls($imageField, array $sizes = [])
    {
        $imageUrl = $this->getAttribute($imageField);
        
        if (!$imageUrl) {
            return [];
        }

        return $this->getCdnManager()->generateResponsiveUrls($imageUrl, $sizes);
    }

    /**
     * Get thumbnail URL
     *
     * @param string $imageField
     * @return string|null
     */
    public function getThumbnailUrl($imageField)
    {
        $thumbnailField = str_replace('_image', '_thumbnail_url', $imageField);
        $thumbnailField = str_replace('_url', '_thumbnail_url', $thumbnailField);
        
        return $this->getAttribute($thumbnailField);
    }

    /**
     * Get WebP URL
     *
     * @param string $imageField
     * @return string|null
     */
    public function getWebPUrl($imageField)
    {
        $webpField = str_replace('_image', '_webp_url', $imageField);
        $webpField = str_replace('_url', '_webp_url', $webpField);
        
        return $this->getAttribute($webpField);
    }

    /**
     * Process and store image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $field
     * @param string $type
     * @param array $options
     * @return array
     */
    public function processAndStoreImage($file, $field, $type = 'cover', array $options = [])
    {
        $processor = $this->getImageProcessor();
        
        // Validate image
        if (!$processor->validateImage($file)) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        // Generate storage path
        $directory = $this->getImageDirectory($type);
        $filename = $this->generateImageFilename($file, $type);
        $storagePath = $file->storeAs('public/' . $directory, $filename);

        // Process image
        $quality = $options['quality'] ?? 'medium';
        $optimizedData = $processor->optimizeImage($storagePath, $quality, $type);

        // Generate thumbnail if requested
        $thumbnailData = null;
        if ($options['generate_thumbnail'] ?? true) {
            try {
                $thumbnailData = $processor->generateThumbnail($optimizedData['local_path']);
            } catch (\Exception $e) {
                \Log::warning("Thumbnail generation failed: " . $e->getMessage());
            }
        }

        // Generate WebP if requested
        $webpData = null;
        if ($options['generate_webp'] ?? true) {
            try {
                $webpData = $processor->convertToWebP($optimizedData['local_path'], $quality);
            } catch (\Exception $e) {
                \Log::warning("WebP conversion failed: " . $e->getMessage());
            }
        }

        // Update model attributes
        $this->setAttribute($field, $optimizedData['url']);
        
        if ($thumbnailData) {
            $thumbnailField = str_replace('_image', '_thumbnail_url', $field);
            $this->setAttribute($thumbnailField, $thumbnailData['url']);
        }
        
        if ($webpData) {
            $webpField = str_replace('_image', '_webp_url', $field);
            $this->setAttribute($webpField, $webpData['url']);
        }

        return [
            'optimized' => $optimizedData,
            'thumbnail' => $thumbnailData,
            'webp' => $webpData
        ];
    }

    /**
     * Get image directory for storage
     *
     * @param string $type
     * @return string
     */
    protected function getImageDirectory($type)
    {
        $modelName = strtolower(class_basename($this));
        
        return "{$modelName}s/{$type}";
    }

    /**
     * Generate unique filename for image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $type
     * @return string
     */
    protected function generateImageFilename($file, $type)
    {
        $extension = $file->getClientOriginalExtension();
        $modelId = $this->getKey() ?? uniqid();
        $timestamp = now()->format('YmdHis');
        
        return "{$modelId}_{$type}_{$timestamp}.{$extension}";
    }

    /**
     * Clean up associated images when model is deleted
     */
    public function cleanupImages()
    {
        $imageFields = $this->getImageFields();
        
        foreach ($imageFields as $field) {
            $this->deleteImageFiles($field);
        }
    }

    /**
     * Delete image files for a specific field
     *
     * @param string $field
     */
    protected function deleteImageFiles($field)
    {
        $imageUrl = $this->getAttribute($field);
        
        if ($imageUrl) {
            // Delete main image
            $this->deleteImageFile($imageUrl);
            
            // Delete thumbnail
            $thumbnailField = str_replace('_image', '_thumbnail_url', $field);
            $thumbnailUrl = $this->getAttribute($thumbnailField);
            if ($thumbnailUrl) {
                $this->deleteImageFile($thumbnailUrl);
            }
            
            // Delete WebP
            $webpField = str_replace('_image', '_webp_url', $field);
            $webpUrl = $this->getAttribute($webpField);
            if ($webpUrl) {
                $this->deleteImageFile($webpUrl);
            }
        }
    }

    /**
     * Delete a single image file
     *
     * @param string $url
     */
    protected function deleteImageFile($url)
    {
        try {
            // Convert URL to storage path
            $path = str_replace(Storage::url(''), '', $url);
            
            // Delete from local storage
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            
            // Delete from CDN if enabled
            $cdnManager = $this->getCdnManager();
            if ($cdnManager->isCdnEnabled()) {
                $cdnManager->deleteImage($path);
            }
            
        } catch (\Exception $e) {
            \Log::warning("Failed to delete image file {$url}: " . $e->getMessage());
        }
    }

    /**
     * Get list of image fields for this model
     * Override this method in your model to specify image fields
     *
     * @return array
     */
    protected function getImageFields()
    {
        return ['cover_image', 'banner_image', 'image_url'];
    }

    /**
     * Get image information
     *
     * @param string $imageField
     * @return array|null
     */
    public function getImageInfo($imageField)
    {
        $imageUrl = $this->getAttribute($imageField);
        
        if (!$imageUrl) {
            return null;
        }

        try {
            $path = str_replace(Storage::url(''), '', $imageUrl);
            return $this->getImageProcessor()->getImageInfo($path);
        } catch (\Exception $e) {
            \Log::warning("Failed to get image info for {$imageField}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if image exists
     *
     * @param string $imageField
     * @return bool
     */
    public function hasImage($imageField)
    {
        $imageUrl = $this->getAttribute($imageField);
        
        if (!$imageUrl) {
            return false;
        }

        $path = str_replace(Storage::url(''), '', $imageUrl);
        return Storage::exists($path);
    }
}