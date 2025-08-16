<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class MangaMissingChaptersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:missing-chapters 
                            {--manga-id= : Check specific manga ID}
                            {--format=table : Output format (table, json, csv)}
                            {--export= : Export results to file}
                            {--fix : Attempt to fix missing chapters by creating placeholders}
                            {--threshold=0.1 : Missing chapter threshold (0.1 = 10%)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect missing chapters in manga series';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mangaId = $this->option('manga-id');
        $format = $this->option('format');
        $exportFile = $this->option('export');
        $fix = $this->option('fix');
        $threshold = (float) $this->option('threshold');

        $this->info('Scanning for missing chapters...');

        try {
            $results = $this->scanForMissingChapters($mangaId, $threshold);

            if (empty($results)) {
                $this->info('No missing chapters detected!');
                return 0;
            }

            $this->displayResults($results, $format);

            if ($exportFile) {
                $this->exportResults($results, $exportFile, $format);
            }

            if ($fix) {
                $this->fixMissingChapters($results);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Scan failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Scan for missing chapters
     */
    protected function scanForMissingChapters($mangaId, $threshold)
    {
        $query = Manga::with(['chapters' => function ($q) {
            $q->orderBy('chapter_number');
        }]);

        if ($mangaId) {
            $query->where('id', $mangaId);
        }

        $mangas = $query->get();
        $results = [];

        foreach ($mangas as $manga) {
            $missingChapters = $this->findMissingChaptersForManga($manga, $threshold);
            
            if (!empty($missingChapters)) {
                $results[] = [
                    'manga_id' => $manga->id,
                    'manga_title' => $manga->title,
                    'total_chapters' => $manga->chapters->count(),
                    'expected_chapters' => $manga->total_chapters,
                    'missing_chapters' => $missingChapters,
                    'missing_count' => count($missingChapters),
                    'missing_percentage' => $this->calculateMissingPercentage($manga, $missingChapters)
                ];
            }
        }

        return $results;
    }

    /**
     * Find missing chapters for a specific manga
     */
    protected function findMissingChaptersForManga($manga, $threshold)
    {
        $chapters = $manga->chapters->pluck('chapter_number')->toArray();
        
        if (empty($chapters)) {
            return [];
        }

        $missingChapters = [];
        $minChapter = min($chapters);
        $maxChapter = max($chapters);

        // Check for gaps in chapter sequence
        for ($i = $minChapter; $i <= $maxChapter; $i += 0.1) {
            $chapterNumber = round($i, 1);
            
            if (!in_array($chapterNumber, $chapters)) {
                $missingChapters[] = $chapterNumber;
            }
        }

        // Filter out insignificant gaps based on threshold
        $totalExpected = ($maxChapter - $minChapter) * 10 + 1; // Account for decimal chapters
        $missingPercentage = count($missingChapters) / $totalExpected;

        if ($missingPercentage < $threshold) {
            return [];
        }

        return $missingChapters;
    }

    /**
     * Calculate missing percentage
     */
    protected function calculateMissingPercentage($manga, $missingChapters)
    {
        if ($manga->total_chapters) {
            return round((count($missingChapters) / $manga->total_chapters) * 100, 2);
        }

        $chapters = $manga->chapters->pluck('chapter_number')->toArray();
        if (empty($chapters)) {
            return 0;
        }

        $maxChapter = max($chapters);
        $expectedTotal = $maxChapter * 10; // Account for decimal chapters
        
        return round((count($missingChapters) / $expectedTotal) * 100, 2);
    }

    /**
     * Display results
     */
    protected function displayResults($results, $format)
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
                break;
                
            case 'csv':
                $this->outputCsv($results);
                break;
                
            default:
                $this->outputTable($results);
                break;
        }
    }

    /**
     * Output results as table
     */
    protected function outputTable($results)
    {
        $tableData = [];
        
        foreach ($results as $result) {
            $missingRange = $this->formatMissingChapters($result['missing_chapters']);
            
            $tableData[] = [
                $result['manga_id'],
                substr($result['manga_title'], 0, 30) . (strlen($result['manga_title']) > 30 ? '...' : ''),
                $result['total_chapters'],
                $result['expected_chapters'] ?? 'Unknown',
                $result['missing_count'],
                $result['missing_percentage'] . '%',
                $missingRange
            ];
        }

        $this->table([
            'ID', 'Title', 'Current', 'Expected', 'Missing', 'Missing %', 'Missing Chapters'
        ], $tableData);
    }

    /**
     * Output results as CSV
     */
    protected function outputCsv($results)
    {
        $this->line('manga_id,title,current_chapters,expected_chapters,missing_count,missing_percentage,missing_chapters');
        
        foreach ($results as $result) {
            $missingChapters = implode(';', $result['missing_chapters']);
            
            $this->line(sprintf(
                '%d,"%s",%d,%s,%d,%.2f,"%s"',
                $result['manga_id'],
                str_replace('"', '""', $result['manga_title']),
                $result['total_chapters'],
                $result['expected_chapters'] ?? 'Unknown',
                $result['missing_count'],
                $result['missing_percentage'],
                $missingChapters
            ));
        }
    }

    /**
     * Format missing chapters for display
     */
    protected function formatMissingChapters($missingChapters)
    {
        if (count($missingChapters) > 10) {
            $sample = array_slice($missingChapters, 0, 5);
            return implode(', ', $sample) . '... +' . (count($missingChapters) - 5) . ' more';
        }
        
        return implode(', ', $missingChapters);
    }

    /**
     * Export results to file
     */
    protected function exportResults($results, $filename, $format)
    {
        $content = '';
        
        switch ($format) {
            case 'json':
                $content = json_encode($results, JSON_PRETTY_PRINT);
                break;
                
            case 'csv':
                $content = "manga_id,title,current_chapters,expected_chapters,missing_count,missing_percentage,missing_chapters\n";
                foreach ($results as $result) {
                    $missingChapters = implode(';', $result['missing_chapters']);
                    $content .= sprintf(
                        "%d,\"%s\",%d,%s,%d,%.2f,\"%s\"\n",
                        $result['manga_id'],
                        str_replace('"', '""', $result['manga_title']),
                        $result['total_chapters'],
                        $result['expected_chapters'] ?? 'Unknown',
                        $result['missing_count'],
                        $result['missing_percentage'],
                        $missingChapters
                    );
                }
                break;
                
            default:
                // Plain text format
                foreach ($results as $result) {
                    $content .= "Manga: {$result['manga_title']} (ID: {$result['manga_id']})\n";
                    $content .= "Current chapters: {$result['total_chapters']}\n";
                    $content .= "Missing chapters: " . implode(', ', $result['missing_chapters']) . "\n\n";
                }
                break;
        }

        file_put_contents($filename, $content);
        $this->info("Results exported to: {$filename}");
    }

    /**
     * Fix missing chapters by creating placeholders
     */
    protected function fixMissingChapters($results)
    {
        if (!$this->confirm('This will create placeholder chapters for missing entries. Continue?')) {
            return;
        }

        $totalFixed = 0;

        foreach ($results as $result) {
            $manga = Manga::find($result['manga_id']);
            
            if (!$manga) {
                continue;
            }

            $this->info("Fixing missing chapters for: {$manga->title}");
            
            foreach ($result['missing_chapters'] as $chapterNumber) {
                try {
                    Chapter::create([
                        'manga_id' => $manga->id,
                        'chapter_number' => $chapterNumber,
                        'title' => "Chapter {$chapterNumber}",
                        'slug' => $manga->slug . '-chapter-' . str_replace('.', '-', $chapterNumber),
                        'page_count' => 0,
                        'published_at' => null // Unpublished placeholder
                    ]);
                    
                    $totalFixed++;
                } catch (\Exception $e) {
                    $this->warn("Failed to create chapter {$chapterNumber} for manga {$manga->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Created {$totalFixed} placeholder chapters.");
        $this->warn("Remember to add content to these placeholder chapters!");
    }
}