# ReadingProgress Model Documentation

The `ReadingProgress` model handles tracking user reading progress for manga chapters, supporting both authenticated users and guest users.

## Features

- **Automatic Progress Tracking**: Automatically saves reading progress as users read
- **Guest User Support**: Uses session storage for non-authenticated users
- **Progress Migration**: Migrates guest progress to user account upon login
- **Completion Tracking**: Tracks chapter completion status
- **Progress Percentage**: Calculates overall manga reading progress
- **Cleanup Utilities**: Provides methods to clean up old progress records

## Basic Usage

### Updating Reading Progress

```php
// For authenticated users (automatically detects current user)
ReadingProgress::updateProgress($mangaId, $chapterId, $pageNumber);

// For specific user
ReadingProgress::updateProgress($mangaId, $chapterId, $pageNumber, $userId);

// For guest users (saves to session)
ReadingProgress::updateProgress($mangaId, $chapterId, $pageNumber);
```

### Getting Reading Progress

```php
// Get progress for current user or guest
$progress = ReadingProgress::getProgress($mangaId);

// Get progress for specific user
$progress = ReadingProgress::getProgress($mangaId, $userId);

// For authenticated users, returns ReadingProgress model instance
// For guest users, returns array with progress data
```

### Marking Chapter as Completed

```php
// Mark chapter as completed (sets page to last page)
ReadingProgress::markChapterCompleted($mangaId, $chapterId);

// Check if chapter is completed
$isCompleted = ReadingProgress::isChapterCompleted($mangaId, $chapterId);
```

### Progress Percentage

```php
// Get overall reading progress percentage for a manga
$percentage = ReadingProgress::getProgressPercentage($mangaId, $userId);
// Returns float (0.0 to 100.0)
```

### Getting All Progress

```php
// Get all reading progress for current user
$allProgress = ReadingProgress::getAllProgress();

// Get all progress for specific user
$allProgress = ReadingProgress::getAllProgress($userId);

// For authenticated users, returns Collection of ReadingProgress models
// For guest users, returns array of progress data
```

## Guest User Support

The model automatically handles guest users by storing progress in the session:

```php
// Session structure for guest progress
Session::get('reading_progress') = [
    $mangaId => [
        'manga_id' => $mangaId,
        'chapter_id' => $chapterId,
        'page_number' => $pageNumber,
        'completed_at' => $timestamp
    ]
];
```

### Migrating Guest Progress

When a guest user logs in, you can migrate their session progress to their account:

```php
// Migrate guest progress to user account
$migratedCount = ReadingProgress::migrateGuestProgress($userId);

// This will:
// 1. Copy all session progress to database
// 2. Skip manga where user already has progress
// 3. Clear session progress after migration
// 4. Return number of records migrated
```

## Relationships

The model has the following relationships:

```php
// Belongs to User (nullable for guest users)
$progress->user;

// Belongs to Manga
$progress->manga;

// Belongs to Chapter
$progress->chapter;
```

## Database Schema

```sql
CREATE TABLE reading_progress (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL, -- Nullable for guest users
    manga_id BIGINT UNSIGNED NOT NULL,
    chapter_id BIGINT UNSIGNED NOT NULL,
    page_number INT NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manga_id) REFERENCES mangas(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_manga (user_id, manga_id),
    INDEX idx_user_progress (user_id, updated_at),
    INDEX idx_manga_progress (manga_id)
);
```

## Maintenance

### Cleanup Old Records

```php
// Clean up progress records older than 365 days (default)
$deletedCount = ReadingProgress::cleanupOldProgress();

// Clean up records older than specific number of days
$deletedCount = ReadingProgress::cleanupOldProgress(180); // 6 months
```

## Usage in Controllers

### Reader Controller Example

```php
class ReaderController extends Controller
{
    public function show(Manga $manga, Chapter $chapter)
    {
        // Get current progress
        $progress = ReadingProgress::getProgress($manga->id);
        
        // Load chapter with pages
        $chapter->load('pages');
        
        return view('reader.show', compact('manga', 'chapter', 'progress'));
    }
    
    public function saveProgress(Request $request)
    {
        $validated = $request->validate([
            'manga_id' => 'required|exists:mangas,id',
            'chapter_id' => 'required|exists:chapters,id',
            'page_number' => 'required|integer|min:1'
        ]);
        
        ReadingProgress::updateProgress(
            $validated['manga_id'],
            $validated['chapter_id'],
            $validated['page_number']
        );
        
        return response()->json(['success' => true]);
    }
}
```

### User Login Handler Example

```php
class LoginController extends Controller
{
    protected function authenticated(Request $request, $user)
    {
        // Migrate guest progress when user logs in
        $migratedCount = ReadingProgress::migrateGuestProgress($user->id);
        
        if ($migratedCount > 0) {
            session()->flash('message', "Migrated reading progress for {$migratedCount} manga.");
        }
    }
}
```

## JavaScript Integration

For frontend integration, you can create AJAX calls to save progress:

```javascript
// Save reading progress via AJAX
function saveReadingProgress(mangaId, chapterId, pageNumber) {
    fetch('/api/reading-progress', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            manga_id: mangaId,
            chapter_id: chapterId,
            page_number: pageNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Progress saved successfully');
        }
    })
    .catch(error => {
        console.error('Error saving progress:', error);
    });
}

// Auto-save progress when user scrolls to a new page
let currentPage = 1;
function onPageChange(newPage) {
    if (newPage !== currentPage) {
        currentPage = newPage;
        saveReadingProgress(mangaId, chapterId, newPage);
    }
}
```

## Requirements Fulfilled

This implementation fulfills the following requirements:

- **4.1**: Automatic progress tracking ✓
- **4.2**: Continue from last read position ✓  
- **4.3**: Reading progress indicators ✓
- **4.4**: Separate progress for each manga ✓
- **4.5**: Local storage for guest users ✓

The model provides a comprehensive solution for tracking manga reading progress with support for both authenticated and guest users, automatic progress migration, and efficient database operations.