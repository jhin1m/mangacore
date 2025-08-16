<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Services\ImageProcessor;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\Manga;

class GenerateThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:generate-thumbnails 
                            {--type=all : Type of thumbnails to generate (all, pages, covers)}
                            {--manga-id= : Specific manga ID to process}
                            {--batch-size=50 : Number of images to process in each batch}
                            {--force : Force regeneration of existing thumbnails}
                            {--width= : Custom thumbnail width}
                            {--height= : Custom thumbnail height}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnails for manga images';

    /**
     * Image processor instance
     */
    protected $imageProcessor;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->imageProcessor = new ImageProcessor();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        $mangaId = $this->option('manga-id');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');
        $customWidth = $this->option('width');
        $customHeight = $this->option('height');

        $dimensions = null;
        if ($customWidth && $customHeight) {
            $dimensions = ['width' => (int) $customWidth, 'height' => (int) $customHeight];
        }

        $this->info('Starting thumbnail generation...');
        $this->info("Type: {$type}");
        $this->info("Batch size: {$batchSize}");
        
        if ($dimensions) {
            $this->info("Custom dimensions: {$dimensions['width']}x{$dimensions['height']}");
        }

        $totalProcessed = 0;
        $totalErrors = 0;

        try {
            if ($type === 'all' || $type === 'pages') {
                $result = $this->generatePageThumbnails($mangaId, $batchSize, $force, $dimensions);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            if ($type === 'all' || $type === 'covers') {
                $result = $this->generateCoverThumbnails($mangaId, $batchSize, $force, $dimensions);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            $this->info("Thumbnail generation completed!");
            $this->info("Total processed: {$totalProcessed}");
            
            if ($totalErrors > 0) {
                $this->warn("Total errors: {$totalErrors}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Thumbnail generation failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Generate thumbnails for page images
     */
    protected function generatePageThumbnails($mangaId, $batchSize, $force, $dimensions)
    {
        $this->info('Generating page thumbnails...');
        
        $query = Page::query();
        
        if ($mangaId) {
            $query->whereHas('chapter', function ($q) use ($mangaId) {
                $q->where('manga_id', $mangaId);
            });
        }

        if (!$force) {
            // Only process pages that don't have thumbnails
            $query->whereNull('thumbnail_url');
        }

        $totalPages = $query->count();
        $processed = 0;
        $errors = 0;

        if ($totalPages === 0) {
            $this->info('No pages need thumbnail generation.');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->info("Found {$totalPages} pages to process.");
        $progressBar = $this->output->createProgressBar($totalPages);

        $query->chunk($batchSize, function ($pages) use ($dimensions, &$processed, &$errors, $progressBar) {
            foreach ($pages as $page) {
                try {
                    if (Storage::exists($page->image_url)) {
                        $thumbnailData = $this->imageProcessor->generateThumbnail(
                            $page->image_url,
                            $dimensions
                        );

                        // Update page with thumbnail URL
                        $page->update([
                            'thumbnail_url' => $thumbnailData['url'],
                            'thumbnail_generated_at' => now()
                        ]);

                        $processed++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to generate thumbnail for page {$page->id}: " . $e->getMessage());
                    $errors++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        return ['processed' => $processed, 'errors' => $errors];
    }

    /**
     * Generate thumbnails for cover images
     */
    protected function generateCoverThumbnails($mangaId, $batchSize, $force, $dimensions)
    {
        $this->info('Generating cover thumbnails...');
        
        $query = Manga::whereNotNull('cover_image');
        
        if ($mangaId) {
            $query->where('id', $mangaId);
        }

        if (!$force) {
            $query->whereNull('cover_thumbnail_url');
        }

        $totalMangas = $query->count();
        $processed = 0;
        $errors = 0;

        if ($totalMangas === 0) {
            $this->info('No cover images need thumbnail generation.');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->info("Found {$totalMangas} cover images to process.");
        $progressBar = $this->output->createProgressBar($totalMangas);

        $query->chunk($batchSize, function ($mangas) use ($dimensions, &$processed, &$errors, $progressBar) {
            foreach ($mangas as $manga) {
                try {
                    if (Storage::exists($manga->cover_image)) {
                        $thumbnailData = $this->imageProcessor->generateThumbnail(
                            $manga->cover_image,
                            $dimensions
                        );

                        // Update manga with thumbnail URL
                        $manga->update([
                            'cover_thumbnail_url' => $thumbnailData['url'],
                            'cover_thumbnail_generated_at' => now()
                        ]);

                        $processed++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to generate thumbnail for manga {$manga->id}: " . $e->getMessage());
                    $errors++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        return ['processed' => $processed, 'errors' => $errors];
    }
}