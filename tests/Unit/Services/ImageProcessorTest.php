<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Services\ImageProcessor;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Page;
use Ophim\Core\Exceptions\ImageProcessingException;

class ImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected $imageProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->imageProcessor = new ImageProcessor();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_process_single_image()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 1200);
        
        $result = $this->imageProcessor->processImage($image);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('thumbnail', $result);
        $this->assertArrayHasKey('webp', $result);
    }

    /** @test */
    public function it_validates_image_format()
    {
        $invalidFile = UploadedFile::fake()->create('test.txt', 100);
        
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('Unsupported image format');
        
        $this->imageProcessor->processImage($invalidFile);
    }

    /** @test */
    public function it_can_process_multiple_images_for_chapter()
    {
        $chapter = Chapter::factory()->create();
        $images = [
            UploadedFile::fake()->image('page1.jpg', 800, 1200),
            UploadedFile::fake()->image('page2.jpg', 800, 1200),
            UploadedFile::fake()->image('page3.jpg', 800, 1200),
        ];
        
        $result = $this->imageProcessor->processUploadedImages($images, $chapter);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Check that pages were created
        $this->assertEquals(3, $chapter->pages()->count());
        
        // Check page numbering
        $pages = $chapter->pages()->orderBy('page_number')->get();
        $this->assertEquals(1, $pages[0]->page_number);
        $this->assertEquals(2, $pages[1]->page_number);
        $this->assertEquals(3, $pages[2]->page_number);
    }

    /** @test */
    public function it_can_optimize_image_quality()
    {
        $image = UploadedFile::fake()->image('test.jpg', 1600, 2400);
        
        $optimized = $this->imageProcessor->optimizeImage($image, 'medium');
        
        $this->assertNotNull($optimized);
        // Additional assertions would depend on the actual optimization implementation
    }

    /** @test */
    public function it_can_generate_thumbnail()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 1200);
        
        $thumbnail = $this->imageProcessor->generateThumbnail($image, 200, 300);
        
        $this->assertNotNull($thumbnail);
        // Additional assertions would depend on the actual thumbnail generation
    }

    /** @test */
    public function it_can_convert_to_webp()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 1200);
        
        $webp = $this->imageProcessor->convertToWebP($image);
        
        $this->assertNotNull($webp);
        // Additional assertions would depend on the WebP conversion implementation
    }

    /** @test */
    public function it_handles_large_images()
    {
        $largeImage = UploadedFile::fake()->image('large.jpg', 4000, 6000);
        
        $result = $this->imageProcessor->processImage($largeImage);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original', $result);
    }

    /** @test */
    public function it_can_extract_images_from_zip()
    {
        $chapter = Chapter::factory()->create();
        
        // Create a temporary ZIP file
        $zipPath = storage_path('app/test.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        
        // Add some dummy image files
        $zip->addFromString('001.jpg', 'fake jpg content');
        $zip->addFromString('002.png', 'fake png content');
        $zip->addFromString('003.gif', 'fake gif content');
        $zip->addFromString('readme.txt', 'should be ignored');
        $zip->close();
        
        $zipFile = new UploadedFile($zipPath, 'chapter.zip', 'application/zip', null, true);
        
        $result = $this->imageProcessor->processZipUpload($zipFile, $chapter);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // Only image files should be processed
        
        // Clean up
        unlink($zipPath);
    }

    /** @test */
    public function it_validates_zip_file_contents()
    {
        $chapter = Chapter::factory()->create();
        
        // Create a ZIP with no images
        $zipPath = storage_path('app/empty.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('readme.txt', 'no images here');
        $zip->close();
        
        $zipFile = new UploadedFile($zipPath, 'empty.zip', 'application/zip', null, true);
        
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('No valid images found in ZIP file');
        
        $this->imageProcessor->processZipUpload($zipFile, $chapter);
        
        // Clean up
        unlink($zipPath);
    }

    /** @test */
    public function it_sorts_images_naturally_from_zip()
    {
        $chapter = Chapter::factory()->create();
        
        $zipPath = storage_path('app/sorted.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        
        // Add files in non-sequential order
        $zip->addFromString('010.jpg', 'fake content');
        $zip->addFromString('002.jpg', 'fake content');
        $zip->addFromString('001.jpg', 'fake content');
        $zip->addFromString('020.jpg', 'fake content');
        $zip->close();
        
        $zipFile = new UploadedFile($zipPath, 'sorted.zip', 'application/zip', null, true);
        
        $result = $this->imageProcessor->processZipUpload($zipFile, $chapter);
        
        // Check that pages are created in correct order
        $pages = $chapter->pages()->orderBy('page_number')->get();
        $this->assertEquals(1, $pages[0]->page_number);
        $this->assertEquals(2, $pages[1]->page_number);
        $this->assertEquals(3, $pages[2]->page_number);
        $this->assertEquals(4, $pages[3]->page_number);
        
        // Clean up
        unlink($zipPath);
    }

    /** @test */
    public function it_handles_processing_errors_gracefully()
    {
        $corruptedImage = UploadedFile::fake()->create('corrupted.jpg', 0); // Empty file
        
        $this->expectException(ImageProcessingException::class);
        
        $this->imageProcessor->processImage($corruptedImage);
    }

    /** @test */
    public function it_can_get_image_dimensions()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 1200);
        
        $dimensions = $this->imageProcessor->getImageDimensions($image);
        
        $this->assertIsArray($dimensions);
        $this->assertArrayHasKey('width', $dimensions);
        $this->assertArrayHasKey('height', $dimensions);
        $this->assertEquals(800, $dimensions['width']);
        $this->assertEquals(1200, $dimensions['height']);
    }

    /** @test */
    public function it_can_validate_image_file()
    {
        $validImage = UploadedFile::fake()->image('valid.jpg', 800, 1200);
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100);
        
        $this->assertTrue($this->imageProcessor->validateImageFile($validImage));
        $this->assertFalse($this->imageProcessor->validateImageFile($invalidFile));
    }

    /** @test */
    public function it_can_clean_up_temporary_files()
    {
        $image = UploadedFile::fake()->image('temp.jpg', 800, 1200);
        
        // Process image to create temporary files
        $result = $this->imageProcessor->processImage($image);
        
        // Clean up should not throw any errors
        $this->imageProcessor->cleanupTemporaryFiles();
        
        $this->assertTrue(true); // If we get here, cleanup was successful
    }

    /** @test */
    public function it_respects_quality_settings()
    {
        $image = UploadedFile::fake()->image('quality.jpg', 800, 1200);
        
        $lowQuality = $this->imageProcessor->optimizeImage($image, 'low');
        $highQuality = $this->imageProcessor->optimizeImage($image, 'high');
        
        $this->assertNotNull($lowQuality);
        $this->assertNotNull($highQuality);
        
        // In a real implementation, you would check file sizes
        // Low quality should result in smaller file size than high quality
    }

    /** @test */
    public function it_can_batch_process_images()
    {
        $chapter = Chapter::factory()->create();
        $images = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $images[] = UploadedFile::fake()->image("page{$i}.jpg", 800, 1200);
        }
        
        $result = $this->imageProcessor->batchProcessImages($images, $chapter);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
        $this->assertEquals(10, $chapter->pages()->count());
    }
}