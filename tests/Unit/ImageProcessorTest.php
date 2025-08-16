<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Services\ImageProcessor;
use Ophim\Core\Services\CDNManager;
use Ophim\Core\Exceptions\ImageProcessingException;

class ImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected $imageProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        $this->imageProcessor = new ImageProcessor();
    }

    /** @test */
    public function it_can_validate_image_files()
    {
        // Valid image
        $validImage = UploadedFile::fake()->image('test.jpg', 800, 600);
        $this->assertTrue($this->imageProcessor->validateImage($validImage));

        // Invalid file type
        $invalidFile = UploadedFile::fake()->create('test.txt', 100);
        $this->assertFalse($this->imageProcessor->validateImage($invalidFile));
    }

    /** @test */
    public function it_can_optimize_images()
    {
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $storagePath = $image->store('public/test');

        $result = $this->imageProcessor->optimizeImage($storagePath, 'medium', 'page');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('optimized_url', $result);
    }

    /** @test */
    public function it_can_generate_thumbnails()
    {
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $storagePath = $image->store('public/test');

        $result = $this->imageProcessor->generateThumbnail($storagePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('dimensions', $result);
    }

    /** @test */
    public function it_can_convert_to_webp()
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('WebP support not available');
        }

        $image = UploadedFile::fake()->image('test.jpg', 800, 600);
        $storagePath = $image->store('public/test');

        $result = $this->imageProcessor->convertToWebP($storagePath, 'high');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('webp', $result['format']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_image_format()
    {
        $this->expectException(ImageProcessingException::class);
        
        // Create a fake file with unsupported extension
        $invalidFile = UploadedFile::fake()->create('test.bmp', 100);
        $storagePath = $invalidFile->store('public/test');

        $this->imageProcessor->optimizeImage($storagePath);
    }

    /** @test */
    public function it_throws_exception_for_missing_file()
    {
        $this->expectException(ImageProcessingException::class);
        
        $this->imageProcessor->optimizeImage('public/nonexistent.jpg');
    }

    /** @test */
    public function it_can_batch_optimize_images()
    {
        $image1 = UploadedFile::fake()->image('test1.jpg', 800, 600);
        $image2 = UploadedFile::fake()->image('test2.jpg', 1200, 800);
        
        $path1 = $image1->store('public/test');
        $path2 = $image2->store('public/test');

        $results = $this->imageProcessor->batchOptimize([$path1, $path2], 'medium', 'page');

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
    }

    /** @test */
    public function it_can_resize_images()
    {
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $storagePath = $image->store('public/test');

        $result = $this->imageProcessor->resizeImage($storagePath, 400, 300, 'small');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dimensions', $result);
        $this->assertEquals('small', $result['suffix']);
    }

    /** @test */
    public function it_can_get_image_info()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);
        $storagePath = $image->store('public/test');

        $info = $this->imageProcessor->getImageInfo($storagePath);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('width', $info);
        $this->assertArrayHasKey('height', $info);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('format', $info);
    }

    /** @test */
    public function it_can_generate_multiple_sizes()
    {
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $storagePath = $image->store('public/test');

        $sizes = [
            'small' => ['width' => 300, 'height' => 200],
            'medium' => ['width' => 600, 'height' => 400],
            'large' => ['width' => 900, 'height' => 600]
        ];

        $results = $this->imageProcessor->generateMultipleSizes($storagePath, $sizes);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('small', $results);
        $this->assertArrayHasKey('medium', $results);
        $this->assertArrayHasKey('large', $results);
    }
}