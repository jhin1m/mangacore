# Batch Processing and Maintenance Commands

This document describes the batch processing and maintenance commands available in MangaCore for managing manga content efficiently.

## Available Commands

### 1. manga:import-chapter

Import a chapter from a ZIP file containing page images.

**Signature:**
```bash
php artisan manga:import-chapter {manga_id} {chapter_number} {zip_path} [options]
```

**Arguments:**
- `manga_id`: The ID of the manga
- `chapter_number`: The chapter number (supports decimals like 4.5)
- `zip_path`: Path to the ZIP file containing chapter pages

**Options:**
- `--title=`: Optional chapter title
- `--volume=`: Volume number
- `--premium`: Mark chapter as premium
- `--publish-now`: Publish immediately
- `--schedule=`: Schedule publishing (Y-m-d H:i:s format)

**Examples:**
```bash
# Import chapter 1 for manga ID 5
php artisan manga:import-chapter 5 1 /path/to/chapter1.zip

# Import with custom title and publish immediately
php artisan manga:import-chapter 5 1.5 /path/to/chapter1-5.zip --title="Special Chapter" --publish-now

# Import and schedule for later
php artisan manga:import-chapter 5 2 /path/to/chapter2.zip --schedule="2024-01-15 10:00:00"
```

### 2. manga:optimize-images

Optimize manga images for better performance.

**Signature:**
```bash
php artisan manga:optimize-images [options]
```

**Options:**
- `--quality=medium`: Quality level (low, medium, high)
- `--type=all`: Image type to optimize (all, pages, covers, banners)
- `--manga-id=`: Specific manga ID to optimize
- `--batch-size=50`: Number of images to process in each batch
- `--force`: Force re-optimization of already optimized images

**Examples:**
```bash
# Optimize all images with medium quality
php artisan manga:optimize-images

# Optimize only cover images with high quality
php artisan manga:optimize-images --type=covers --quality=high

# Optimize images for specific manga
php artisan manga:optimize-images --manga-id=5 --force

# Process in smaller batches
php artisan manga:optimize-images --batch-size=25
```

### 3. manga:generate-thumbnails

Generate thumbnails for manga images.

**Signature:**
```bash
php artisan manga:generate-thumbnails [options]
```

**Options:**
- `--type=all`: Type of thumbnails to generate (all, pages, covers)
- `--manga-id=`: Specific manga ID to process
- `--batch-size=50`: Number of images to process in each batch
- `--force`: Force regeneration of existing thumbnails
- `--width=`: Custom thumbnail width
- `--height=`: Custom thumbnail height

**Examples:**
```bash
# Generate all thumbnails
php artisan manga:generate-thumbnails

# Generate only page thumbnails
php artisan manga:generate-thumbnails --type=pages

# Generate with custom dimensions
php artisan manga:generate-thumbnails --width=200 --height=300

# Force regeneration for specific manga
php artisan manga:generate-thumbnails --manga-id=5 --force
```

### 4. manga:missing-chapters

Detect missing chapters in manga series.

**Signature:**
```bash
php artisan manga:missing-chapters [options]
```

**Options:**
- `--manga-id=`: Check specific manga ID
- `--format=table`: Output format (table, json, csv)
- `--export=`: Export results to file
- `--fix`: Attempt to fix missing chapters by creating placeholders
- `--threshold=0.1`: Missing chapter threshold (0.1 = 10%)

**Examples:**
```bash
# Check all manga for missing chapters
php artisan manga:missing-chapters

# Check specific manga
php artisan manga:missing-chapters --manga-id=5

# Export results to CSV
php artisan manga:missing-chapters --format=csv --export=missing_chapters.csv

# Fix missing chapters by creating placeholders
php artisan manga:missing-chapters --fix

# Use custom threshold (only report if >20% missing)
php artisan manga:missing-chapters --threshold=0.2
```

### 5. manga:clean-cache

Clean manga-related cache data.

**Signature:**
```bash
php artisan manga:clean-cache [options]
```

**Options:**
- `--type=all`: Cache type to clean (all, model, view, image, session, database)
- `--manga-id=`: Clean cache for specific manga ID
- `--older-than=`: Clean cache older than specified time (e.g., 1d, 2h, 30m)
- `--force`: Force clean without confirmation
- `--dry-run`: Show what would be cleaned without actually cleaning

**Examples:**
```bash
# Clean all cache (with confirmation)
php artisan manga:clean-cache

# Clean only model cache
php artisan manga:clean-cache --type=model

# Clean cache older than 1 day
php artisan manga:clean-cache --older-than=1d

# Dry run to see what would be cleaned
php artisan manga:clean-cache --dry-run

# Force clean without confirmation
php artisan manga:clean-cache --force

# Clean cache for specific manga
php artisan manga:clean-cache --manga-id=5 --type=model
```

## Usage Scenarios

### Bulk Chapter Import

For importing multiple chapters at once:

```bash
# Create a script to import multiple chapters
for i in {1..10}; do
    php artisan manga:import-chapter 5 $i "/path/to/chapters/chapter_$i.zip" --publish-now
done
```

### Maintenance Routine

Regular maintenance tasks:

```bash
# Daily maintenance
php artisan manga:optimize-images --type=pages --quality=medium
php artisan manga:generate-thumbnails --force
php artisan manga:clean-cache --older-than=1d

# Weekly maintenance
php artisan manga:missing-chapters --export=weekly_missing_report.csv
php artisan manga:clean-cache --type=all --older-than=7d
```

### Performance Optimization

For improving site performance:

```bash
# Optimize all images for better loading
php artisan manga:optimize-images --quality=high --force

# Generate thumbnails for faster previews
php artisan manga:generate-thumbnails --type=all --force

# Clean old cache data
php artisan manga:clean-cache --older-than=3d
```

## Scheduling Commands

Add these commands to your Laravel scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily image optimization
    $schedule->command('manga:optimize-images --type=pages')
             ->daily()
             ->withoutOverlapping();

    // Weekly thumbnail generation
    $schedule->command('manga:generate-thumbnails --force')
             ->weekly()
             ->withoutOverlapping();

    // Daily cache cleanup
    $schedule->command('manga:clean-cache --older-than=1d --force')
             ->daily();

    // Weekly missing chapter report
    $schedule->command('manga:missing-chapters --export=storage/reports/missing_chapters.csv')
             ->weekly();
}
```

## Error Handling

All commands include comprehensive error handling:

- **Validation**: Input parameters are validated before processing
- **Progress Tracking**: Long-running operations show progress bars
- **Error Recovery**: Failed operations are logged and can be retried
- **Rollback**: Import operations can be rolled back if they fail
- **Dry Run**: Most commands support dry-run mode for testing

## Performance Considerations

- **Batch Processing**: Commands process data in configurable batches to avoid memory issues
- **Time Limits**: Long-running commands are designed to handle PHP time limits
- **Resource Usage**: Commands monitor memory usage and can pause if needed
- **Concurrent Execution**: Commands use locking to prevent concurrent execution conflicts

## Monitoring and Logging

Commands log their activities to Laravel's logging system:

- **Info Level**: Normal operations and progress updates
- **Warning Level**: Non-critical issues that don't stop execution
- **Error Level**: Critical errors that require attention
- **Debug Level**: Detailed information for troubleshooting

Check logs in `storage/logs/laravel.log` for command execution details.