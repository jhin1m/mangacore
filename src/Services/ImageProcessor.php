<?php

namespace Ophim\Core\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ophim\Core\Exceptions\ImageProcessingException;

class ImageProcessor
{
    /**
     * Supported image formats
     */
    const SUPPORTED_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];

    /**
     * Quality settings for different optimization levels
     */
    const QUALITY_SETTINGS = [
        'low' => 60,
        'medium' => 80,
        'high' => 95
    ];

    /**
     * Maximum dimensions for different image types
     */
    const MAX_DIMENSIONS = [
        'page' => ['width' => 1600, 'height' => 2400],
        'thumbnail' => ['width' => 300, 'height' => 450],
        'cover' => ['width' => 800, 'height' => 1200],
        'banner' => ['width' => 1920, 'height' => 1080]
    ];

    /**
     * CDN Manager instance
     */
    protected $cdnManager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cdnManager = new CDNManager();
    }

    /**
     * Optimize uploaded image
     *
     * @param string $storagePath Path to the stored image
     * @param string $quality Quality level: low, medium, high
     * @param string $type Image type (page, thumbnail, cover, banner)
     * @return array Optimized image data with URLs
     */
    public function optimizeImage($storagePath, $quality = 'medium', $type = 'page')
    {
        if (!Storage::exists($storagePath)) {
            throw ImageProcessingException::fileNotFound($storagePath);
        }

        $fullPath = Storage::path($storagePath);
        $pathInfo = pathinfo($fullPath);
        $extension = strtolower($pathInfo['extension']);

        // Check if format is supported
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            throw ImageProcessingException::invalidFormat($extension);
        }

        try {
            // Optimize image
            $optimizedPath = $this->performOptimization($fullPath, $pathInfo, $quality, $type);
            
            // Upload to CDN if enabled
            $cdnPath = $this->generateCdnPath($optimizedPath, $type);
            $cdnUrl = $this->cdnManager->uploadImage($optimizedPath, $cdnPath, $type);
            
            return [
                'local_path' => $optimizedPath,
                'cdn_path' => $cdnPath,
                'url' => $cdnUrl,
                'optimized_url' => $this->cdnManager->getOptimizedUrl($cdnUrl, $quality)
            ];
            
        } catch (\Exception $e) {
            throw ImageProcessingException::compressionFailed($e->getMessage());
        }
    }

    /**
     * Generate thumbnail from image
     *
     * @param string $storagePath Path to the source image
     * @param array $dimensions Custom dimensions [width, height]
     * @return array Thumbnail data with URLs
     */
    public function generateThumbnail($storagePath, $dimensions = null)
    {
        if (!Storage::exists($storagePath)) {
            throw ImageProcessingException::fileNotFound($storagePath);
        }

        $fullPath = Storage::path($storagePath);
        $pathInfo = pathinfo($fullPath);

        // Use custom dimensions or default thumbnail dimensions
        $width = $dimensions['width'] ?? self::MAX_DIMENSIONS['thumbnail']['width'];
        $height = $dimensions['height'] ?? self::MAX_DIMENSIONS['thumbnail']['height'];

        try {
            $thumbnailPath = $this->performThumbnailGeneration($fullPath, $pathInfo, $width, $height);
            
            // Upload to CDN
            $cdnPath = $this->generateCdnPath($thumbnailPath, 'thumbnail');
            $cdnUrl = $this->cdnManager->uploadImage($thumbnailPath, $cdnPath, 'thumbnail');
            
            return [
                'local_path' => $thumbnailPath,
                'cdn_path' => $cdnPath,
                'url' => $cdnUrl,
                'dimensions' => ['width' => $width, 'height' => $height]
            ];
            
        } catch (\Exception $e) {
            throw ImageProcessingException::thumbnailGenerationFailed($e->getMessage());
        }
    }

    /**
     * Convert image to WebP format
     *
     * @param string $storagePath Path to the source image
     * @param string $quality Quality level
     * @return array WebP image data with URLs
     */
    public function convertToWebP($storagePath, $quality = 'high')
    {
        if (!Storage::exists($storagePath)) {
            throw ImageProcessingException::fileNotFound($storagePath);
        }

        // Check if WebP is supported
        if (!function_exists('imagewebp') && !class_exists('\Intervention\Image\Facades\Image')) {
            throw ImageProcessingException::webpConversionFailed("WebP format is not supported on this server");
        }

        $fullPath = Storage::path($storagePath);
        $pathInfo = pathinfo($fullPath);

        try {
            $webpPath = $this->performWebPConversion($fullPath, $pathInfo, $quality);
            
            // Upload to CDN
            $cdnPath = $this->generateCdnPath($webpPath, 'webp');
            $cdnUrl = $this->cdnManager->uploadImage($webpPath, $cdnPath, 'webp');
            
            return [
                'local_path' => $webpPath,
                'cdn_path' => $cdnPath,
                'url' => $cdnUrl,
                'format' => 'webp',
                'quality' => $quality
            ];
            
        } catch (\Exception $e) {
            throw ImageProcessingException::webpConversionFailed($e->getMessage());
        }
    }

    /**
     * Process uploaded images for a chapter
     *
     * @param array $images Array of uploaded image files
     * @param \Ophim\Core\Models\Chapter $chapter Chapter model
     * @param array $options Processing options
     * @return array Processed image data
     */
    public function processUploadedImages(array $images, $chapter, array $options = [])
    {
        $processedImages = [];
        $quality = $options['quality'] ?? 'medium';
        $generateWebP = $options['generate_webp'] ?? true;
        $generateThumbnails = $options['generate_thumbnails'] ?? true;
        
        foreach ($images as $index => $image) {
            try {
                // Validate image
                if (!$this->validateImage($image)) {
                    throw ImageProcessingException::processingFailed("Invalid image file at index {$index}");
                }
                
                // Generate storage path
                $filename = 'chapters/' . $chapter->id . '/page_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . '.' . $image->getClientOriginalExtension();
                $storagePath = $image->storeAs('public', $filename);
                
                // Optimize image
                $optimizedData = $this->optimizeImage($storagePath, $quality, 'page');
                
                $imageData = [
                    'original_path' => $storagePath,
                    'optimized_data' => $optimizedData,
                    'page_number' => $index + 1,
                    'urls' => [
                        'original' => Storage::url($storagePath),
                        'optimized' => $optimizedData['url'],
                        'optimized_quality' => $optimizedData['optimized_url']
                    ]
                ];
                
                // Generate thumbnail if requested
                if ($generateThumbnails) {
                    try {
                        $thumbnailData = $this->generateThumbnail($optimizedData['local_path']);
                        $imageData['thumbnail_data'] = $thumbnailData;
                        $imageData['urls']['thumbnail'] = $thumbnailData['url'];
                    } catch (\Exception $e) {
                        Log::warning("Thumbnail generation failed for {$filename}: " . $e->getMessage());
                        $imageData['thumbnail_data'] = null;
                        $imageData['urls']['thumbnail'] = null;
                    }
                }
                
                // Try to convert to WebP if requested
                if ($generateWebP) {
                    try {
                        $webpData = $this->convertToWebP($optimizedData['local_path'], $quality);
                        $imageData['webp_data'] = $webpData;
                        $imageData['urls']['webp'] = $webpData['url'];
                    } catch (\Exception $e) {
                        Log::warning("WebP conversion failed for {$filename}: " . $e->getMessage());
                        $imageData['webp_data'] = null;
                        $imageData['urls']['webp'] = null;
                    }
                }
                
                // Generate responsive URLs if CDN supports it
                if ($this->cdnManager->isCdnEnabled()) {
                    $imageData['responsive_urls'] = $this->cdnManager->generateResponsiveUrls($optimizedData['url']);
                }
                
                $processedImages[] = $imageData;
                
            } catch (\Exception $e) {
                throw ImageProcessingException::processingFailed("Failed to process image {$index}: " . $e->getMessage());
            }
        }
        
        return $processedImages;
    }

    /**
     * Validate image file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return bool
     */
    public function validateImage($file)
    {
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return false;
        }
        
        // Check MIME type
        $allowedMimes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }
        
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }
        
        return true;
    }

    /**
     * Get image information
     *
     * @param string $storagePath
     * @return array
     */
    public function getImageInfo($storagePath)
    {
        if (!Storage::exists($storagePath)) {
            throw new \Exception("Image file not found: {$storagePath}");
        }

        $fullPath = Storage::path($storagePath);
        
        try {
            if (class_exists('\Intervention\Image\Facades\Image')) {
                $image = \Intervention\Image\Facades\Image::make($fullPath);
                
                return [
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'size' => Storage::size($storagePath),
                    'mime_type' => $image->mime(),
                    'format' => strtolower(pathinfo($fullPath, PATHINFO_EXTENSION))
                ];
            } else {
                // Use getimagesize for GD fallback
                $imageInfo = getimagesize($fullPath);
                if (!$imageInfo) {
                    throw new \Exception("Unable to get image information");
                }
                
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'size' => Storage::size($storagePath),
                    'mime_type' => $imageInfo['mime'],
                    'format' => strtolower(pathinfo($fullPath, PATHINFO_EXTENSION))
                ];
            }
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to get image info: " . $e->getMessage());
        }
    }

    /**
     * Optimize image using Intervention Image library
     */
    protected function optimizeWithIntervention($fullPath, $pathInfo, $quality, $type = 'page')
    {
        $image = \Intervention\Image\Facades\Image::make($fullPath);
        
        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        
        // Get max dimensions for the image type
        $maxWidth = self::MAX_DIMENSIONS[$type]['width'] ?? self::MAX_DIMENSIONS['page']['width'];
        $maxHeight = self::MAX_DIMENSIONS[$type]['height'] ?? self::MAX_DIMENSIONS['page']['height'];
        
        // Resize if necessary while maintaining aspect ratio
        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Apply quality compression
        $qualityValue = self::QUALITY_SETTINGS[$quality] ?? self::QUALITY_SETTINGS['medium'];
        
        // Generate optimized filename
        $optimizedFilename = $pathInfo['filename'] . '_optimized.' . $pathInfo['extension'];
        $optimizedPath = $pathInfo['dirname'] . '/' . $optimizedFilename;
        
        // Save optimized image
        $image->save($optimizedPath, $qualityValue);
        
        // Convert storage path
        $relativePath = str_replace(Storage::path(''), '', $optimizedPath);
        
        return $relativePath;
    }

    /**
     * Generate thumbnail using Intervention Image library
     */
    protected function generateThumbnailWithIntervention($fullPath, $pathInfo, $width, $height)
    {
        $image = \Intervention\Image\Facades\Image::make($fullPath);
        
        // Resize to thumbnail dimensions
        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Generate thumbnail filename
        $thumbnailFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        $thumbnailPath = $pathInfo['dirname'] . '/' . $thumbnailFilename;
        
        // Save thumbnail
        $image->save($thumbnailPath, self::QUALITY_SETTINGS['medium']);
        
        // Convert to relative path
        $relativePath = str_replace(Storage::path(''), '', $thumbnailPath);
        
        return $relativePath;
    }

    /**
     * Convert to WebP using Intervention Image library
     */
    protected function convertToWebPWithIntervention($fullPath, $pathInfo, $quality)
    {
        $image = \Intervention\Image\Facades\Image::make($fullPath);
        
        // Generate WebP filename
        $webpFilename = $pathInfo['filename'] . '.webp';
        $webpPath = $pathInfo['dirname'] . '/' . $webpFilename;
        
        // Get quality value
        $qualityValue = self::QUALITY_SETTINGS[$quality] ?? self::QUALITY_SETTINGS['high'];
        
        // Save as WebP
        $image->encode('webp', $qualityValue)->save($webpPath);
        
        // Convert to relative path
        $relativePath = str_replace(Storage::path(''), '', $webpPath);
        
        return $relativePath;
    }

    /**
     * Resize image using Intervention Image library
     */
    protected function resizeWithIntervention($fullPath, $pathInfo, $width, $height, $suffix)
    {
        $image = \Intervention\Image\Facades\Image::make($fullPath);
        
        // Resize to specified dimensions
        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Generate resized filename
        $resizedFilename = $pathInfo['filename'] . '_' . $suffix . '.' . $pathInfo['extension'];
        $resizedPath = $pathInfo['dirname'] . '/' . $resizedFilename;
        
        // Save resized image
        $image->save($resizedPath, self::QUALITY_SETTINGS['medium']);
        
        // Convert to relative path
        $relativePath = str_replace(Storage::path(''), '', $resizedPath);
        
        return $relativePath;
    }

    /**
     * Optimize image using GD library (fallback)
     */
    protected function optimizeWithGD($fullPath, $pathInfo, $quality, $type = 'page')
    {
        $extension = strtolower($pathInfo['extension']);
        
        // Create image resource based on type
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'gif':
                $image = imagecreatefromgif($fullPath);
                break;
            default:
                throw new \Exception("Unsupported image format for GD: {$extension}");
        }
        
        if (!$image) {
            throw new \Exception("Failed to create image resource from: {$fullPath}");
        }
        
        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Calculate new dimensions
        $maxWidth = self::MAX_DIMENSIONS[$type]['width'] ?? self::MAX_DIMENSIONS['page']['width'];
        $maxHeight = self::MAX_DIMENSIONS[$type]['height'] ?? self::MAX_DIMENSIONS['page']['height'];
        
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
        
        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Generate optimized filename
        $optimizedFilename = $pathInfo['filename'] . '_optimized.' . $pathInfo['extension'];
        $optimizedPath = $pathInfo['dirname'] . '/' . $optimizedFilename;
        
        // Save optimized image
        $qualityValue = self::QUALITY_SETTINGS[$quality] ?? self::QUALITY_SETTINGS['medium'];
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $optimizedPath, $qualityValue);
                break;
            case 'png':
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = (int)(9 - ($qualityValue / 100) * 9);
                imagepng($newImage, $optimizedPath, $pngQuality);
                break;
            case 'gif':
                imagegif($newImage, $optimizedPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        // Convert storage path
        $relativePath = str_replace(Storage::path(''), '', $optimizedPath);
        
        return $relativePath;
    }

    /**
     * Generate thumbnail using GD library
     */
    protected function generateThumbnailWithGD($fullPath, $pathInfo)
    {
        $extension = strtolower($pathInfo['extension']);
        
        // Create image resource
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'gif':
                $image = imagecreatefromgif($fullPath);
                break;
            default:
                throw new \Exception("Unsupported image format for thumbnail: {$extension}");
        }
        
        if (!$image) {
            throw new \Exception("Failed to create image resource for thumbnail");
        }
        
        // Get dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Calculate thumbnail dimensions
        $maxWidth = self::MAX_DIMENSIONS['thumbnail']['width'];
        $maxHeight = self::MAX_DIMENSIONS['thumbnail']['height'];
        
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save thumbnail
        $thumbnailFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        $thumbnailPath = $pathInfo['dirname'] . '/' . $thumbnailFilename;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $thumbnailPath, self::QUALITY_SETTINGS['medium']);
                break;
            case 'png':
                $pngQuality = (int)(9 - (self::QUALITY_SETTINGS['medium'] / 100) * 9);
                imagepng($thumbnail, $thumbnailPath, $pngQuality);
                break;
            case 'gif':
                imagegif($thumbnail, $thumbnailPath);
                break;
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($thumbnail);
        
        // Convert to relative path
        $relativePath = str_replace(Storage::path(''), '', $thumbnailPath);
        
        return $relativePath;
    }

    /**
     * Convert to WebP using GD library
     */
    protected function convertToWebPWithGD($fullPath, $pathInfo, $quality)
    {
        $extension = strtolower($pathInfo['extension']);
        
        // Create image resource
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'gif':
                $image = imagecreatefromgif($fullPath);
                break;
            default:
                throw new \Exception("Unsupported image format for WebP conversion: {$extension}");
        }
        
        if (!$image) {
            throw new \Exception("Failed to create image resource for WebP conversion");
        }
        
        // Generate WebP filename
        $webpFilename = $pathInfo['filename'] . '.webp';
        $webpPath = $pathInfo['dirname'] . '/' . $webpFilename;
        
        // Get quality value
        $qualityValue = self::QUALITY_SETTINGS[$quality] ?? self::QUALITY_SETTINGS['high'];
        
        // Save as WebP
        if (!imagewebp($image, $webpPath, $qualityValue)) {
            imagedestroy($image);
            throw new \Exception("Failed to save WebP image");
        }
        
        // Clean up
        imagedestroy($image);
        
        // Convert to relative path
        $relativePath = str_replace(Storage::path(''), '', $webpPath);
        
        return $relativePath;
    }

    /**
     * Resize image using GD library
     */
    protected function resizeWithGD($fullPath, $pathInfo, $width, $height, $suffix)
    {
        $extension = strtolower($pathInfo['extension']);
        
        // Create image resource based on type
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'gif':
                $image = imagecreatefromgif($fullPath);
                break;
            default:
                throw new \Exception("Unsupported image format for resize: {$extension}");
        }
        
        if (!$image) {
            throw new \Exception("Failed to create image resource for resize");
        }
        
        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($width / $originalWidth, $height / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Generate resized filename
        $resizedFilename = $pathInfo['filename'] . '_' . $suffix . '.' . $pathInfo['extension'];
        $resizedPath = $pathInfo['dirname'] . '/' . $resizedFilename;
        
        // Save resized image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $resizedPath, self::QUALITY_SETTINGS['medium']);
                break;
            case 'png':
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = (int)(9 - (self::QUALITY_SETTINGS['medium'] / 100) * 9);
                imagepng($newImage, $resizedPath, $pngQuality);
                break;
            case 'gif':
                imagegif($newImage, $resizedPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        // Convert storage path
        $relativePath = str_replace(Storage::path(''), '', $resizedPath);
        
        return $relativePath;
    }

    /**
     * Clean up temporary files
     *
     * @param array $filePaths
     */
    public function cleanupFiles(array $filePaths)
    {
        foreach ($filePaths as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }

    /**
     * Batch optimize images
     *
     * @param array $imagePaths
     * @param string $quality
     * @param string $type
     * @return array Results
     */
    public function batchOptimize(array $imagePaths, $quality = 'medium', $type = 'page')
    {
        $results = [];
        
        foreach ($imagePaths as $path) {
            try {
                $optimizedData = $this->optimizeImage($path, $quality, $type);
                $results[] = [
                    'success' => true,
                    'original_path' => $path,
                    'optimized_data' => $optimizedData
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'original_path' => $path,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Process ZIP file containing chapter images
     *
     * @param \Illuminate\Http\UploadedFile $zipFile
     * @param \Ophim\Core\Models\Chapter $chapter
     * @param array $options
     * @return array Processed images data
     */
    public function processZipUpload($zipFile, $chapter, array $options = [])
    {
        $extractPath = storage_path('app/temp/zip_extract_' . uniqid());
        
        try {
            // Create extraction directory
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            
            // Extract ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($zipFile->getPathname()) !== true) {
                throw ImageProcessingException::processingFailed("Failed to open ZIP file");
            }
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            // Get image files from extracted directory
            $imageFiles = $this->getImageFilesFromDirectory($extractPath);
            
            if (empty($imageFiles)) {
                throw ImageProcessingException::processingFailed("No valid image files found in ZIP");
            }
            
            // Sort files naturally (page_1.jpg, page_2.jpg, etc.)
            natsort($imageFiles);
            
            // Convert file paths to UploadedFile objects
            $uploadedFiles = [];
            foreach ($imageFiles as $index => $filePath) {
                $uploadedFiles[] = new \Illuminate\Http\UploadedFile(
                    $filePath,
                    basename($filePath),
                    mime_content_type($filePath),
                    null,
                    true
                );
            }
            
            // Process images
            $processedImages = $this->processUploadedImages($uploadedFiles, $chapter, $options);
            
            return $processedImages;
            
        } finally {
            // Clean up extraction directory
            $this->cleanupDirectory($extractPath);
        }
    }

    /**
     * Generate multiple image sizes
     *
     * @param string $storagePath
     * @param array $sizes Array of ['name' => ['width' => int, 'height' => int]]
     * @return array Generated sizes data
     */
    public function generateMultipleSizes($storagePath, array $sizes)
    {
        $generatedSizes = [];
        
        foreach ($sizes as $sizeName => $dimensions) {
            try {
                $resizedData = $this->resizeImage($storagePath, $dimensions['width'], $dimensions['height'], $sizeName);
                $generatedSizes[$sizeName] = $resizedData;
            } catch (\Exception $e) {
                Log::warning("Failed to generate size {$sizeName} for {$storagePath}: " . $e->getMessage());
                $generatedSizes[$sizeName] = null;
            }
        }
        
        return $generatedSizes;
    }

    /**
     * Resize image to specific dimensions
     *
     * @param string $storagePath
     * @param int $width
     * @param int $height
     * @param string $suffix
     * @return array Resized image data
     */
    public function resizeImage($storagePath, $width, $height, $suffix = 'resized')
    {
        if (!Storage::exists($storagePath)) {
            throw ImageProcessingException::fileNotFound($storagePath);
        }

        $fullPath = Storage::path($storagePath);
        $pathInfo = pathinfo($fullPath);

        try {
            $resizedPath = $this->performResize($fullPath, $pathInfo, $width, $height, $suffix);
            
            // Upload to CDN
            $cdnPath = $this->generateCdnPath($resizedPath, $suffix);
            $cdnUrl = $this->cdnManager->uploadImage($resizedPath, $cdnPath, $suffix);
            
            return [
                'local_path' => $resizedPath,
                'cdn_path' => $cdnPath,
                'url' => $cdnUrl,
                'dimensions' => ['width' => $width, 'height' => $height],
                'suffix' => $suffix
            ];
            
        } catch (\Exception $e) {
            throw ImageProcessingException::processingFailed("Failed to resize image: " . $e->getMessage());
        }
    }

    /**
     * Get image files from directory recursively
     *
     * @param string $directory
     * @return array
     */
    protected function getImageFilesFromDirectory($directory)
    {
        $imageFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, self::SUPPORTED_FORMATS)) {
                    $imageFiles[] = $file->getPathname();
                }
            }
        }
        
        return $imageFiles;
    }

    /**
     * Clean up directory and its contents
     *
     * @param string $directory
     */
    protected function cleanupDirectory($directory)
    {
        if (is_dir($directory)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            
            rmdir($directory);
        }
    }

    /**
     * Generate CDN path for image
     *
     * @param string $localPath
     * @param string $type
     * @return string
     */
    protected function generateCdnPath($localPath, $type)
    {
        // Remove storage path prefix and public prefix
        $relativePath = str_replace([Storage::path(''), 'public/'], '', $localPath);
        
        // Add type prefix for organization
        return $type . '/' . ltrim($relativePath, '/');
    }

    /**
     * Perform actual image optimization
     *
     * @param string $fullPath
     * @param array $pathInfo
     * @param string $quality
     * @param string $type
     * @return string
     */
    protected function performOptimization($fullPath, $pathInfo, $quality, $type)
    {
        if (class_exists('\Intervention\Image\Facades\Image')) {
            return $this->optimizeWithIntervention($fullPath, $pathInfo, $quality, $type);
        } else {
            return $this->optimizeWithGD($fullPath, $pathInfo, $quality, $type);
        }
    }

    /**
     * Perform thumbnail generation
     *
     * @param string $fullPath
     * @param array $pathInfo
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function performThumbnailGeneration($fullPath, $pathInfo, $width, $height)
    {
        if (class_exists('\Intervention\Image\Facades\Image')) {
            return $this->generateThumbnailWithIntervention($fullPath, $pathInfo, $width, $height);
        } else {
            return $this->generateThumbnailWithGD($fullPath, $pathInfo, $width, $height);
        }
    }

    /**
     * Perform WebP conversion
     *
     * @param string $fullPath
     * @param array $pathInfo
     * @param string $quality
     * @return string
     */
    protected function performWebPConversion($fullPath, $pathInfo, $quality)
    {
        if (class_exists('\Intervention\Image\Facades\Image')) {
            return $this->convertToWebPWithIntervention($fullPath, $pathInfo, $quality);
        } else {
            return $this->convertToWebPWithGD($fullPath, $pathInfo, $quality);
        }
    }

    /**
     * Perform image resize
     *
     * @param string $fullPath
     * @param array $pathInfo
     * @param int $width
     * @param int $height
     * @param string $suffix
     * @return string
     */
    protected function performResize($fullPath, $pathInfo, $width, $height, $suffix)
    {
        if (class_exists('\Intervention\Image\Facades\Image')) {
            return $this->resizeWithIntervention($fullPath, $pathInfo, $width, $height, $suffix);
        } else {
            return $this->resizeWithGD($fullPath, $pathInfo, $width, $height, $suffix);
        }
    }
}