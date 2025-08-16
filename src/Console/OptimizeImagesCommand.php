<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Services\ImageProcessor;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\Manga;

class OptimizeImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:optimize-images 
                            {--quality=medium : Quality level (low, medium, high)}
                            {--type=all : Image type to optimize (all, pages, covers, banners)}
                            {--manga-id= : Specific manga ID to optimize}
                            {--batch-size=50 : Number of images to process in each batch}
                            {--force : Force re-optimization of already optimized images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize manga images for better performance';

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
        $quality = $this->option('quality');
        $type = $this->option('type');
        $mangaId = $this->option('manga-id');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->info('Starting image optimization...');
        $this->info("Quality: {$quality}");
        $this->info("Type: {$type}");
        $this->info("Batch size: {$batchSize}");

        $totalProcessed = 0;
        $totalErrors = 0;

        try {
            if ($type === 'all' || $type === 'pages') {
                $result = $this->optimizePages($quality, $mangaId, $batchSize, $force);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            if ($type === 'all' || $type === 'covers') {
                $result = $this->optimizeCovers($quality, $mangaId, $batchSize, $force);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            if ($type === 'all' || $type === 'banners') {
                $result = $this->optimizeBanners($quality, $mangaId, $batchSize, $force);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            $this->info("Optimization completed!");
            $this->info("Total processed: {$totalProcessed}");
            
            if ($totalErrors > 0) {
                $this->warn("Total errors: {$totalErrors}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Optimization failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Optimize page images
     */
    protected function optimizePages($quality, $mangaId, $batchSize, $force)
    {
        $this->info('Optimizing page images...');
        
        $query = Page::query();
        
        if ($mangaId) {
            $query->whereHas('chapter', function ($q) use ($mangaId) {
                $q->where('manga_id', $mangaId);
            });
        }

        if (!$force) {
            // Only process pages that haven't been optimized
            $query->where(function ($q) {
                $q->whereNull('optimized_at')
                  ->orWhere('optimized_at', '<', now()->subDays(30));
            });
        }

        $totalPages = $query->count();
        $processed = 0;
        $errors = 0;

        if ($totalPages === 0) {
            $this->info('No pages to optimize.');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->info("Found {$totalPages} pages to optimize.");
        $progressBar = $this->output->createProgressBar($totalPages);

        $query->chunk($batchSize, function ($pages) use ($quality, &$processed, &$errors, $progressBar) {
            foreach ($pages as $page) {
                try {
                    if (Storage::exists($page->image_url)) {
                        $optimizedData = $this->imageProcessor->optimizeImage(
                            $page->image_url, 
                            $quality, 
                            'page'
                        );

                        // Update page with optimized URL
                        $page->update([
                            'image_url' => $optimizedData['url'],
                            'optimized_at' => now()
                        ]);

                        $processed++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to optimize page {$page->id}: " . $e->getMessage());
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
     * Optimize cover images
     */
    protected function optimizeCovers($quality, $mangaId, $batchSize, $force)
    {
        $this->info('Optimizing cover images...');
        
        $query = Manga::whereNotNull('cover_image');
        
        if ($mangaId) {
            $query->where('id', $mangaId);
        }

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('cover_optimized_at')
                  ->orWhere('cover_optimized_at', '<', now()->subDays(30));
            });
        }

        $totalMangas = $query->count();
        $processed = 0;
        $errors = 0;

        if ($totalMangas === 0) {
            $this->info('No cover images to optimize.');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->info("Found {$totalMangas} cover images to optimize.");
        $progressBar = $this->output->createProgressBar($totalMangas);

        $query->chunk($batchSize, function ($mangas) use ($quality, &$processed, &$errors, $progressBar) {
            foreach ($mangas as $manga) {
                try {
                    if (Storage::exists($manga->cover_image)) {
                        $optimizedData = $this->imageProcessor->optimizeImage(
                            $manga->cover_image, 
                            $quality, 
                            'cover'
                        );

                        // Update manga with optimized URL
                        $manga->update([
                            'cover_image' => $optimizedData['url'],
                            'cover_optimized_at' => now()
                        ]);

                        $processed++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to optimize cover for manga {$manga->id}: " . $e->getMessage());
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
     * Optimize banner images
     */
    protected function optimizeBanners($quality, $mangaId, $batchSize, $force)
    {
        $this->info('Optimizing banner images...');
        
        $query = Manga::whereNotNull('banner_image');
        
        if ($mangaId) {
            $query->where('id', $mangaId);
        }

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('banner_optimized_at')
                  ->orWhere('banner_optimized_at', '<', now()->subDays(30));
            });
        }

        $totalMangas = $query->count();
        $processed = 0;
        $errors = 0;

        if ($totalMangas === 0) {
            $this->info('No banner images to optimize.');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->info("Found {$totalMangas} banner images to optimize.");
        $progressBar = $this->output->createProgressBar($totalMangas);

        $query->chunk($batchSize, function ($mangas) use ($quality, &$processed, &$errors, $progressBar) {
            foreach ($mangas as $manga) {
                try {
                    if (Storage::exists($manga->banner_image)) {
                        $optimizedData = $this->imageProcessor->optimizeImage(
                            $manga->banner_image, 
                            $quality, 
                            'banner'
                        );

                        // Update manga with optimized URL
                        $manga->update([
                            'banner_image' => $optimizedData['url'],
                            'banner_optimized_at' => now()
                        ]);

                        $processed++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to optimize banner for manga {$manga->id}: " . $e->getMessage());
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