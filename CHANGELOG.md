# Changelog

All notable changes to MangaCore will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-16

### Added
- Complete refactor from OphimCore movie streaming CMS to MangaCore manga reading platform
- New Manga model replacing Movie model with manga-specific fields (type, demographic, reading_direction)
- New Chapter model replacing Episode model with decimal chapter numbering support
- New Page model for managing individual manga pages
- New Volume model for organizing chapters into volumes
- New ReadingProgress model for tracking user reading progress
- Renamed taxonomy models: Actor→Author, Director→Artist, Studio→Publisher, Region→Origin
- Multi-mode manga reader with support for:
  - Single-page reading
  - Double-page reading
  - Vertical scroll (webtoon style)
  - Horizontal swipe navigation
- Image processing and optimization system with WebP conversion
- Automatic thumbnail generation for manga pages
- Reading progress tracking with bookmark functionality
- RESTful API endpoints for manga data and reading progress
- Admin interface built on Backpack/Laravel with manga-specific CRUD operations
- Batch processing commands for content management:
  - `manga:import-chapter` - Batch chapter uploads
  - `manga:optimize-images` - Bulk image optimization
  - `manga:missing-chapters` - Detect missing content
  - `manga:clean-cache` - Cache management
  - `manga:generate-thumbnails` - Thumbnail generation
- SEO optimization with Schema.org Book/Comic structured data
- Comprehensive test suite covering all components
- User preference system for reading modes and image quality
- CDN integration for image serving
- Rate limiting for API endpoints

### Changed
- Complete database schema transformation from movie to manga structure
- Updated admin dashboard to show manga-specific statistics
- Transformed all movie-related views to manga-focused interfaces
- Updated sitemap generation for manga content
- Modified theme system to support manga reader components

### Removed
- All movie-related models (Movie, Episode) and their associated files
- Video player assets (JWPlayer, HLS.js)
- Movie-specific controllers, policies, and requests
- Episode streaming functionality
- Movie-related database migrations (kept for reference)
- Video player JavaScript dependencies

### Security
- Added API rate limiting middleware
- Implemented proper image upload validation
- Added CSRF protection for all forms
- Secure file handling for batch uploads

### Performance
- Implemented aggressive caching for manga and chapter data
- Added database indexes for common manga queries
- Optimized image loading with lazy loading and preloading
- CDN integration for faster image delivery
- Query optimization for API endpoints

### Breaking Changes
- Complete package namespace change from movie to manga domain
- All API endpoints changed from movie/episode to manga/chapter structure
- Database schema completely restructured
- Admin routes and interface completely updated
- Theme system requires manga-specific components

## [Unreleased]

### Planned
- Advanced search functionality with full-text search
- User rating and review system
- Social features (favorites, reading lists, recommendations)
- Mobile app API enhancements
- Advanced image processing options
- Multi-language support for manga metadata
- Integration with external manga databases
- Advanced analytics and reporting
- Automated content import from various sources