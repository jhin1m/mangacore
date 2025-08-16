<?php

namespace Ophim\Core\Exceptions;

use Exception;

class ImageProcessingException extends Exception
{
    public static function invalidFormat($format)
    {
        return new static("Unsupported image format: {$format}");
    }

    public static function compressionFailed($reason)
    {
        return new static("Image compression failed: {$reason}");
    }

    public static function uploadFailed($destination)
    {
        return new static("Failed to upload image to: {$destination}");
    }

    public static function thumbnailGenerationFailed($reason)
    {
        return new static("Thumbnail generation failed: {$reason}");
    }

    public static function webpConversionFailed($reason)
    {
        return new static("WebP conversion failed: {$reason}");
    }

    public static function cdnUploadFailed($reason)
    {
        return new static("CDN upload failed: {$reason}");
    }

    public static function fileNotFound($path)
    {
        return new static("Image file not found: {$path}");
    }

    public static function invalidDimensions($width, $height)
    {
        return new static("Invalid image dimensions: {$width}x{$height}");
    }

    public static function processingFailed($reason)
    {
        return new static("Image processing failed: {$reason}");
    }
}