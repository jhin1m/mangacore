# Chapter Model Documentation

## Overview

The `Chapter` model represents individual chapters within a manga. It replaces the `Episode` model from the original movie-focused system and provides comprehensive functionality for manga chapter management.

## Features

### Core Functionality
- **Fractional Chapter Numbers**: Supports decimal chapter numbers (e.g., 4.5, 4.6) for special chapters
- **URL Generation**: Automatic URL generation for chapter reading pages
- **SEO Optimization**: Complete SEO tag generation with Schema.org markup
- **Caching**: Built-in caching mechanisms for performance optimization
- **View Tracking**: Automatic view count tracking with spam prevention

### Relationships
- **Manga**: Each chapter belongs to a manga
- **Volume**: Optional relationship to volume organization
- **Pages**: One-to-many relationship with page images
- **Reading Progress**: Tracks user reading progress

### Key Methods

#### URL and Navigation
- `getUrl()`: Generate chapter reading URL
- `getNextChapter()`: Get next chapter in sequence
- `getPreviousChapter()`: Get previous chapter in sequence

#### SEO and Metadata
- `generateSeoTags()`: Generate complete SEO tags including:
  - Meta tags with proper descriptions and keywords
  - Open Graph tags for social media
  - Twitter Card tags
  - JSON-LD structured data with Book/Comic schema
  - Breadcrumb navigation

#### Utility Methods
- `getFormattedTitle()`: Get formatted chapter title
- `incrementViewCount()`: Increment view count with caching
- `openChapter()`: Admin interface button for opening chapter

### Scopes

#### Publication Status
- `published()`: Only published chapters
- `premium()`: Premium chapters only
- `free()`: Free chapters only

#### Ordering
- `orderByChapter($direction)`: Order by chapter number

### Attributes and Casts

#### Database Fields
```php
'manga_id'       // Foreign key to manga
'volume_id'      // Optional foreign key to volume
'title'          // Chapter title (optional)
'slug'           // URL slug (auto-generated)
'chapter_number' // Decimal(4,1) for fractional chapters
'volume_number'  // Volume number (optional)
'page_count'     // Number of pages in chapter
'view_count'     // View counter
'published_at'   // Publication timestamp
'is_premium'     // Premium content flag
```

#### Automatic Casts
```php
'chapter_number' => 'decimal:1'  // Ensures proper decimal formatting
'published_at'   => 'datetime'   // Carbon instance
'is_premium'     => 'boolean'    // Boolean casting
```

### Validation and Security

#### Chapter Number Validation
- Supports fractional numbers (4.5, 4.6, etc.)
- Automatic formatting to 1 decimal place
- Unique constraint per manga

#### Slug Generation
- Auto-generated from title or chapter number
- URL-safe formatting
- Fallback to "chapter-{number}" format

### Caching Strategy

#### Performance Optimization
- Manga relationship caching (5-minute TTL)
- Next/previous chapter caching
- View count increment throttling (5-minute cooldown)

#### Cache Keys
```php
"cache_manga_by_id:{manga_id}"     // Manga relationship
"next_chapter_{chapter_id}"        // Next chapter
"prev_chapter_{chapter_id}"        // Previous chapter
"chapter_view_increment_{id}"      // View increment throttle
```

### SEO Implementation

#### Schema.org Markup
- **Chapter Type**: Structured data for individual chapters
- **Book Relationship**: Links to parent manga as Book type
- **Author/Artist Data**: Includes creator information
- **Position Data**: Chapter number and page count

#### Breadcrumb Navigation
1. Home
2. Origins (regions)
3. Categories
4. Manga title
5. Current chapter

#### Meta Tags
- Title optimization with pattern support
- Description from manga content
- Keywords from manga tags
- Canonical URLs
- Publication timestamps

### Usage Examples

#### Creating a Chapter
```php
$chapter = Chapter::create([
    'manga_id' => $manga->id,
    'title' => 'The Beginning',
    'chapter_number' => 1.0,
    'page_count' => 20,
    'published_at' => now(),
]);
```

#### Fractional Chapters
```php
$specialChapter = Chapter::create([
    'manga_id' => $manga->id,
    'title' => 'Special Episode',
    'chapter_number' => 4.5,  // Between chapters 4 and 5
    'page_count' => 15,
]);
```

#### Navigation
```php
$currentChapter = Chapter::find(1);
$nextChapter = $currentChapter->getNextChapter();
$prevChapter = $currentChapter->getPreviousChapter();
```

#### Querying
```php
// Get published chapters for a manga
$chapters = Chapter::where('manga_id', $mangaId)
    ->published()
    ->orderByChapter()
    ->get();

// Get premium chapters
$premiumChapters = Chapter::premium()->get();
```

### Integration Points

#### Admin Interface
- Backpack CRUD integration
- Custom admin buttons
- Bulk operations support

#### Theme System
- URL generation compatible with theme routing
- SEO tag generation for theme templates
- Caching integration for performance

#### API Support
- RESTful endpoint compatibility
- JSON serialization
- Relationship eager loading

### Performance Considerations

#### Database Optimization
- Indexed fields: manga_id, chapter_number, published_at
- Unique constraints prevent duplicates
- Foreign key relationships with proper cascading

#### Caching Strategy
- Relationship caching reduces database queries
- View count throttling prevents spam
- SEO data caching for repeated requests

#### Memory Usage
- Lazy loading for relationships
- Selective field loading
- Efficient query scoping

### Migration from Episode Model

#### Key Differences
- `chapter_number` replaces `episode_number` with decimal support
- `page_count` replaces video-related fields
- `published_at` replaces streaming availability
- Manga relationships replace movie relationships

#### Data Migration
- Episode data can be migrated to chapters
- Chapter numbers can be derived from episode numbers
- Publication dates preserved
- View counts transferred

This model provides a complete foundation for manga chapter management while maintaining compatibility with the existing Laravel package architecture.