# Manga API Documentation

## Overview

The Manga API provides RESTful endpoints for accessing manga data, chapters, and reading progress. All API responses follow a consistent JSON format with proper error handling and pagination.

## Base URL

```
/api/v1
```

## Authentication

- Most endpoints are public and don't require authentication
- Reading progress endpoints require authentication using Laravel Sanctum
- Rate limiting is applied: 120 requests per minute for authenticated users, 60 for guests

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": {...}
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Success message", 
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20,
    "has_more_pages": true,
    "next_page_url": "...",
    "prev_page_url": null
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

## Endpoints

### Manga Endpoints

#### GET /manga
Get paginated list of manga with filtering and sorting.

**Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20, max: 100)
- `sort_by` (string): Sort field (title, publication_year, rating, view_count, created_at, updated_at)
- `sort_order` (string): Sort direction (asc, desc)
- `search` (string): Search in title, original_title, other_name, description
- `type` (string): Filter by type (manga, manhwa, manhua, webtoon)
- `status` (string): Filter by status (ongoing, completed, hiatus, cancelled)
- `demographic` (string): Filter by demographic (shounen, seinen, josei, shoujo, kodomomuke, general)
- `category_id` (array): Filter by category IDs
- `author_id` (array): Filter by author IDs
- `artist_id` (array): Filter by artist IDs
- `tag_id` (array): Filter by tag IDs

**Example:**
```
GET /api/v1/manga?type=manga&status=ongoing&sort_by=view_count&sort_order=desc&per_page=10
```

#### GET /manga/{manga}
Get detailed manga information including chapters and volumes.

#### GET /manga/{manga}/chapters
Get paginated list of chapters for a specific manga.

**Parameters:**
- `page`, `per_page`: Pagination
- `sort_by`: chapter_number, published_at, view_count, created_at
- `sort_order`: asc, desc

#### GET /manga/{manga}/volumes
Get volumes with their chapters for a specific manga.

#### GET /manga/{manga}/stats
Get statistics for a specific manga.

#### GET /manga/{manga}/related
Get related manga recommendations.

#### GET /manga/search/{query}
Search manga by query string.

### Chapter Endpoints

#### GET /chapters/{chapter}
Get chapter details with pages and navigation data.

#### GET /chapters/{chapter}/pages
Get chapter pages with image optimization.

**Parameters:**
- `quality` (string): Image quality (low, medium, high)
- `preload` (int): Number of pages to preload (default: 3)
- `current_page` (int): Current page number

#### GET /chapters/{chapter}/navigation
Get navigation data for chapter (previous/next chapters, chapter list).

#### POST /chapters/{chapter}/preload
Preload chapter data for smooth reading.

**Parameters:**
- `quality` (string): Image quality
- `preload_next` (bool): Whether to preload next chapter

### Reading Progress Endpoints (Authenticated)

#### GET /reading/progress
Get user's reading progress for all manga.

#### GET /reading/progress/{manga}
Get reading progress for specific manga.

#### POST /reading/progress
Save reading progress.

**Body:**
```json
{
  "manga_id": 1,
  "chapter_id": 5,
  "page_number": 10,
  "completed_at": "2024-01-01T12:00:00Z" // optional
}
```

#### PUT /reading/progress/{manga}
Update reading progress for specific manga.

#### DELETE /reading/progress/{manga}
Delete reading progress for specific manga.

#### GET /reading/history
Get reading history with date filtering.

**Parameters:**
- `from_date`, `to_date`: Date range filters
- `page`, `per_page`: Pagination

#### GET /reading/stats
Get reading statistics (total manga read, completed, streaks, etc.).

### Guest Reading Progress

#### POST /reading/guest/progress
Save reading progress for guest users (session-based).

**Body:**
```json
{
  "session_id": "unique_session_id",
  "manga_id": 1,
  "chapter_id": 5,
  "page_number": 10
}
```

#### GET /reading/guest/progress/{sessionId}
Get guest reading progress by session ID.

### Taxonomy Endpoints

#### GET /taxonomies/categories
Get all categories.

#### GET /taxonomies/tags
Get all tags.

#### GET /taxonomies/authors
Get all authors.

#### GET /taxonomies/artists
Get all artists.

#### GET /taxonomies/publishers
Get all publishers.

#### GET /taxonomies/origins
Get all origins.

### Public Endpoints (Lower Rate Limit)

#### GET /public/manga/popular
Get popular manga (sorted by view count).

#### GET /public/manga/latest
Get latest manga (sorted by creation date).

#### GET /public/manga/featured
Get featured/recommended manga.

## Rate Limiting

- Authenticated users: 120 requests per minute
- Guest users: 60 requests per minute
- Public endpoints: 60 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `Retry-After`: Seconds to wait when rate limited (429 response)

## Error Codes

- `400`: Bad Request - Invalid parameters
- `401`: Unauthorized - Authentication required
- `403`: Forbidden - Access denied
- `404`: Not Found - Resource not found
- `422`: Validation Error - Invalid input data
- `429`: Too Many Requests - Rate limit exceeded
- `500`: Internal Server Error - Server error

## Image Optimization

The API supports multiple image quality levels:
- `low`: 60% quality, max width 800px
- `medium`: 80% quality, max width 1200px (default)
- `high`: 95% quality, max width 1600px

WebP format is automatically served when supported by the client.

## Examples

### Get Popular Manga
```bash
curl -X GET "https://yoursite.com/api/v1/public/manga/popular?per_page=5"
```

### Search Manga
```bash
curl -X GET "https://yoursite.com/api/v1/manga/search/naruto?per_page=10"
```

### Get Chapter Pages
```bash
curl -X GET "https://yoursite.com/api/v1/chapters/123/pages?quality=medium&current_page=1"
```

### Save Reading Progress (Authenticated)
```bash
curl -X POST "https://yoursite.com/api/v1/reading/progress" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"manga_id": 1, "chapter_id": 5, "page_number": 10}'
```