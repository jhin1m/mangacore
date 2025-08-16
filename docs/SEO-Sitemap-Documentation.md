# SEO and Sitemap Documentation

## Overview

This document describes the SEO optimization and sitemap generation implementation for the manga refactor of OphimCore. The system has been updated to provide comprehensive SEO support for manga and chapter content with proper Schema.org markup, Open Graph tags, and Twitter Cards.

## Features

### 1. Enhanced SEO Tag Generation

#### Manga SEO Tags
- **Meta Tags**: Title, description, keywords, canonical URL
- **Open Graph**: Book-specific properties with author, genre, and publication data
- **Twitter Cards**: Summary large image with manga status and type information
- **Schema.org**: Book/GraphicNovel markup with comprehensive metadata

#### Chapter SEO Tags
- **Meta Tags**: Chapter-specific title, description, canonical URL
- **Open Graph**: Article properties with chapter and manga information
- **Twitter Cards**: Chapter number and page count information
- **Schema.org**: Chapter markup with isPartOf relationship to parent manga

### 2. Comprehensive Sitemap Generation

The sitemap system generates multiple XML sitemaps for different content types:

- **Main Sitemap Index**: Links to all sub-sitemaps
- **Pages Sitemap**: Static pages and catalogs
- **Categories Sitemap**: Manga categories
- **Origins Sitemap**: Country/region origins (replaces regions)
- **Authors Sitemap**: Manga authors (replaces actors)
- **Artists Sitemap**: Manga artists (replaces directors)
- **Publishers Sitemap**: Manga publishers (replaces studios)
- **Manga Sitemap**: Individual manga pages (chunked for performance)
- **Chapters Sitemap**: Individual chapter pages (chunked, published only)

## Implementation Details

### Schema.org Markup

#### Manga Schema (Book/GraphicNovel)
```json
{
  "@context": "https://schema.org",
  "@type": "Book",
  "bookFormat": "https://schema.org/GraphicNovel",
  "name": "Manga Title",
  "alternateName": "Original Title",
  "description": "Manga description",
  "author": [{"@type": "Person", "name": "Author Name", "url": "author_url"}],
  "illustrator": [{"@type": "Person", "name": "Artist Name", "url": "artist_url"}],
  "publisher": [{"@type": "Organization", "name": "Publisher Name", "url": "publisher_url"}],
  "genre": ["Action", "Adventure"],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 8.5,
    "bestRating": 10,
    "worstRating": 1,
    "reviewCount": 100
  },
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "VND",
    "availability": "https://schema.org/InStock"
  }
}
```

#### Chapter Schema
```json
{
  "@context": "https://schema.org",
  "@type": "Chapter",
  "name": "Chapter Title",
  "position": 1.0,
  "pageStart": 1,
  "pageEnd": 20,
  "isPartOf": {
    "@type": "Book",
    "name": "Manga Title",
    "url": "manga_url",
    "bookFormat": "https://schema.org/GraphicNovel"
  }
}
```

### Open Graph Properties

#### Manga Open Graph
- `og:type`: book
- `og:title`: Manga title
- `og:description`: Manga description
- `og:image`: Cover and banner images
- `og:url`: Canonical manga URL
- `book:author`: Author names
- `book:genre`: Category names
- `book:release_date`: Publication date
- `book:tag`: Tag names

#### Chapter Open Graph
- `og:type`: article
- `og:title`: Chapter title
- `og:description`: Manga description
- `og:image`: Manga cover image
- `og:url`: Canonical chapter URL
- `article:author`: Author names
- `article:published_time`: Chapter publication date
- `article:section`: Category names
- `article:tag`: Tag names

### Twitter Card Properties

#### Manga Twitter Cards
- `twitter:card`: summary_large_image
- `twitter:title`: Manga title
- `twitter:description`: Manga description
- `twitter:image`: Cover image
- `twitter:label1`: Status (Ongoing, Completed, etc.)
- `twitter:data1`: Status value
- `twitter:label2`: Type (Manga, Manhwa, etc.)
- `twitter:data2`: Type value

#### Chapter Twitter Cards
- `twitter:card`: summary_large_image
- `twitter:title`: Chapter title
- `twitter:description`: Manga description
- `twitter:image`: Manga cover image
- `twitter:label1`: Chapter
- `twitter:data1`: Chapter number
- `twitter:label2`: Pages
- `twitter:data2`: Page count

## Sitemap Structure

### File Organization
```
public/
├── sitemap.xml (main index)
└── sitemap/
    ├── page-sitemap.xml
    ├── categories-sitemap.xml
    ├── origins-sitemap.xml
    ├── authors-sitemap.xml
    ├── artists-sitemap.xml
    ├── publishers-sitemap.xml
    ├── manga-sitemap1.xml
    ├── manga-sitemap2.xml
    ├── chapters-sitemap1.xml
    └── chapters-sitemap2.xml
```

### Priority and Frequency Settings
- **Homepage**: Priority 1.0, Hourly updates
- **Categories/Origins**: Priority 0.8, Daily updates
- **Authors/Artists/Publishers**: Priority 0.7, Weekly updates
- **Manga Pages**: Priority 0.8, Daily updates
- **Chapter Pages**: Priority 0.6, Weekly updates

## Usage

### Generating SEO Tags

#### In Controllers
```php
// Manga controller
public function show(Manga $manga)
{
    $manga->generateSeoTags();
    return view('manga.show', compact('manga'));
}

// Chapter/Reader controller
public function show(Chapter $chapter)
{
    $chapter->generateSeoTags();
    return view('reader.show', compact('chapter'));
}
```

### Generating Sitemaps

#### Admin Interface
1. Navigate to Admin Panel → Sitemap
2. Click "Tạo sitemap" button
3. Sitemaps will be generated in `public/sitemap/` directory

#### Programmatically
```php
$controller = new SiteMapController();
$controller->store(new Request());
```

### Canonical URLs

Both Manga and Chapter models provide canonical URL methods:

```php
$manga = Manga::find(1);
$canonicalUrl = $manga->getCanonicalUrl(); // Returns manga URL

$chapter = Chapter::find(1);
$canonicalUrl = $chapter->getCanonicalUrl(); // Returns chapter URL
```

## Breadcrumb Navigation

Both models generate structured breadcrumb data:

### Manga Breadcrumbs
1. Home
2. Origins (if any)
3. Categories (if any)
4. Current Manga

### Chapter Breadcrumbs
1. Home
2. Origins (if any)
3. Categories (if any)
4. Parent Manga
5. Current Chapter

## Performance Considerations

### Sitemap Generation
- **Chunking**: Large datasets are processed in chunks to prevent memory issues
- **Caching**: Manga data is cached during chapter sitemap generation
- **Filtering**: Only published chapters are included in sitemaps
- **Batch Processing**: Multiple sitemaps are created for large content volumes

### SEO Tag Generation
- **Lazy Loading**: Related data is loaded efficiently
- **Image Optimization**: Images are processed through proxy if configured
- **Caching**: Frequently accessed data is cached

## Configuration

### Settings
- `site_routes_manga`: Manga URL pattern
- `site_routes_chapter`: Chapter URL pattern
- `site_image_proxy_enable`: Enable image proxy
- `site_image_proxy_url`: Image proxy URL pattern
- `site_meta_siteName`: Site name for meta tags

### Environment Variables
- `APP_URL`: Base URL for canonical links
- `APP_NAME`: Default site name

## Validation

Use the provided validation script to verify implementation:

```bash
php validate_seo_sitemap.php
```

The script checks:
- Model file existence
- Interface implementation
- Method availability
- Schema.org markup
- Open Graph properties
- Twitter Card properties
- Sitemap controller updates

## Migration from Movie System

### Key Changes
- Movie → Manga
- Episode → Chapter
- Actor → Author
- Director → Artist
- Studio → Publisher
- Region → Origin

### Backward Compatibility
- Old sitemap files are replaced with new structure
- SEO patterns maintain similar structure for easy migration
- URL patterns can be configured to maintain existing URLs

## Best Practices

### SEO Optimization
1. **Unique Titles**: Ensure each manga and chapter has unique, descriptive titles
2. **Quality Descriptions**: Write compelling descriptions under 160 characters
3. **Image Optimization**: Use high-quality cover images with proper alt text
4. **Structured Data**: Maintain consistent Schema.org markup
5. **Canonical URLs**: Always set canonical URLs to prevent duplicate content

### Sitemap Management
1. **Regular Updates**: Regenerate sitemaps when content changes
2. **Monitor Size**: Keep individual sitemaps under 50MB and 50,000 URLs
3. **Submit to Search Engines**: Submit sitemap index to Google Search Console
4. **Error Handling**: Monitor for broken URLs in sitemaps

### Performance
1. **Caching**: Implement appropriate caching for SEO data
2. **Lazy Loading**: Load related data only when needed
3. **Batch Processing**: Use chunked processing for large datasets
4. **CDN Integration**: Serve images through CDN for better performance

## Troubleshooting

### Common Issues

#### Missing SEO Tags
- Verify model implements `SeoInterface`
- Check if `generateSeoTags()` is called in controller
- Ensure related data (authors, categories) is loaded

#### Sitemap Generation Fails
- Check file permissions on `public/sitemap/` directory
- Verify database connections
- Monitor memory usage during generation

#### Broken URLs in Sitemap
- Verify route definitions
- Check model URL generation methods
- Ensure proper slug generation

### Debug Mode
Enable Laravel debug mode to see detailed error messages during SEO tag generation and sitemap creation.

## Future Enhancements

### Planned Features
1. **Multilingual SEO**: Support for multiple languages
2. **Advanced Schema**: Additional schema types for reviews and ratings
3. **Image Sitemaps**: Dedicated image sitemaps for chapter pages
4. **Video Sitemaps**: Support for promotional videos
5. **News Sitemaps**: Fast indexing for new chapters

### Performance Improvements
1. **Async Generation**: Background sitemap generation
2. **Incremental Updates**: Update only changed content
3. **Compression**: Gzip compression for sitemap files
4. **CDN Integration**: Serve sitemaps through CDN