<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Services\ImageProcessor;
use ZipArchive;

class MangaImportChapterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:import-chapter 
                            {manga_id : The ID of the manga}
                            {chapter_number : The chapter number}
                            {zip_path : Path to the ZIP file containing chapter pages}
                            {--title= : Optional chapter title}
                            {--volume= : Volume number}
                            {--premium : Mark chapter as premium}
                            {--publish-now : Publish immediately}
                            {--schedule= : Schedule publishing (Y-m-d H:i:s format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a chapter from a ZIP file containing page images';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mangaId = $this->argument('manga_id');
        $chapterNumber = $this->argument('chapter_number');
        $zipPath = $this->argument('zip_path');
        
        // Validate manga exists
        $manga = Manga::find($mangaId);
        if (!$manga) {
            $this->error("Manga with ID {$mangaId} not found.");
            return 1;
        }
        
        // Validate ZIP file exists
        if (!file_exists($zipPath)) {
            $this->error("ZIP file not found: {$zipPath}");
            return 1;
        }
        
        // Check if chapter already exists
        $existingChapter = Chapter::where('manga_id', $mangaId)
            ->where('chapter_number', $chapterNumber)
            ->first();
            
        if ($existingChapter) {
            if (!$this->confirm("Chapter {$chapterNumber} already exists for this manga. Overwrite?")) {
                $this->info('Import cancelled.');
                return 0;
            }
            
            // Delete existing chapter and its pages
            foreach ($existingChapter->pages as $page) {
                if ($page->image_url && !filter_var($page->image_url, FILTER_VALIDATE_URL)) {
                    Storage::delete('public' . $page->image_url);
                }
                $page->delete();
            }
            $existingChapter->delete();
        }
        
        $this->info("Importing chapter {$chapterNumber} for manga: {$manga->title}");
        
        try {
            // Create chapter
            $publishedAt = null;
            if ($this->option('publish-now')) {
                $publishedAt = now();
            } elseif ($this->option('schedule')) {
                $publishedAt = \Carbon\Carbon::parse($this->option('schedule'));
            }
            
            $chapter = Chapter::create([
                'manga_id' => $mangaId,
                'chapter_number' => $chapterNumber,
                'title' => $this->option('title'),
                'volume_number' => $this->option('volume'),
                'is_premium' => $this->option('premium'),
                'published_at' => $publishedAt
            ]);
            
            $this->info("Created chapter record with ID: {$chapter->id}");
            
            // Process ZIP file
            $imageProcessor = new ImageProcessor();
            $pagesImported = $this->processZipFile($chapter, $zipPath, $imageProcessor);
            
            // Update page count
            $chapter->update(['page_count' => $pagesImported]);
            
            $this->info("Successfully imported {$pagesImported} pages for chapter {$chapterNumber}");
            $this->info("Chapter URL: {$chapter->getUrl()}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            
            // Clean up chapter if it was created
            if (isset($chapter)) {
                $chapter->delete();
            }
            
            return 1;
        }
    }
    
    /**
     * Process ZIP file and extract pages
     */
    protected function processZipFile($chapter, $zipPath, $imageProcessor)
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception('Failed to open ZIP file');
        }
        
        $extractPath = storage_path('app/temp/chapter_import_' . $chapter->id);
        
        // Create extraction directory
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        
        // Extract ZIP contents
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Get all image files
        $imageFiles = $this->getImageFilesFromDirectory($extractPath);
        
        if (empty($imageFiles)) {
            throw new \Exception('No valid image files found in ZIP');
        }
        
        // Sort files naturally
        natsort($imageFiles);
        
        $this->info("Found " . count($imageFiles) . " image files");
        
        // Create progress bar
        $progressBar = $this->output->createProgressBar(count($imageFiles));
        $progressBar->start();
        
        // Process each image
        $pageNumber = 1;
        $pagesImported = 0;
        
        foreach ($imageFiles as $imagePath) {
            try {
                $this->createPageFromFile($chapter, $imagePath, $pageNumber, $imageProcessor);
                $pagesImported++;
                $pageNumber++;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("\nFailed to process image {$imagePath}: " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        $this->line(''); // New line after progress bar
        
        // Clean up temporary files
        $this->cleanupDirectory($extractPath);
        
        return $pagesImported;
    }
    
    /**
     * Get all image files from directory
     */
    protected function getImageFilesFromDirectory($directory)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imageFiles = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $imageExtensions)) {
                    // Skip system files
                    if (strpos($file->getPathname(), '__MACOSX') === false) {
                        $imageFiles[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $imageFiles;
    }
    
    /**
     * Create page from file
     */
    protected function createPageFromFile($chapter, $filePath, $pageNumber, $imageProcessor)
    {
        // Generate storage path
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $filename = 'chapters/' . $chapter->id . '/page_' . str_pad($pageNumber, 3, '0', STR_PAD_LEFT) . '.' . $extension;
        $storagePath = 'public/' . $filename;
        
        // Move file to storage
        Storage::put($storagePath, file_get_contents($filePath));
        
        // Optimize image
        try {
            $optimizedPath = $imageProcessor->optimizeImage($storagePath);
            $imageUrl = Storage::url($optimizedPath);
        } catch (\Exception $e) {
            // If optimization fails, use original
            $imageUrl = Storage::url($storagePath);
        }
        
        // Create page record
        \Ophim\Core\Models\Page::create([
            'chapter_id' => $chapter->id,
            'page_number' => $pageNumber,
            'image_url' => $imageUrl
        ]);
    }
    
    /**
     * Clean up directory
     */
    protected function cleanupDirectory($directory)
    {
        if (is_dir($directory)) {
            $files = array_diff(scandir($directory), array('.', '..'));
            foreach ($files as $file) {
                $path = $directory . '/' . $file;
                is_dir($path) ? $this->cleanupDirectory($path) : unlink($path);
            }
            rmdir($directory);
        }
    }
}