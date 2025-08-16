# MangaCore

A comprehensive Laravel package for manga and comic content management systems. MangaCore provides a complete solution for building manga reading platforms with robust admin interface, theme system, and optimized reading experience.

## Features

### 📚 Content Management
- **Manga Catalog**: Complete manga database with chapters, pages, authors, artists, publishers
- **Chapter Management**: Batch upload, page reordering, image optimization
- **Volume Organization**: Support for volume-based chapter organization
- **Metadata Support**: Categories, tags, demographics, reading directions, publication info

### 🎨 Reading Experience
- **Multiple Reading Modes**: Single page, double page, vertical scroll, horizontal swipe
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Image Optimization**: WebP conversion, multiple quality levels, lazy loading
- **Progress Tracking**: Automatic bookmark saving and reading history

### ⚡ Performance
- **CDN Integration**: Built-in CDN support for image delivery
- **Caching System**: Multi-layer caching for optimal performance
- **Image Processing**: Automatic compression and thumbnail generation
- **Database Optimization**: Proper indexing and query optimization

### 🛠️ Admin Interface
- **Backpack Integration**: Powerful admin interface built on Backpack/Laravel
- **Batch Operations**: Bulk upload, image optimization, content management
- **User Management**: Role-based permissions and user preferences
- **Analytics Dashboard**: Reading statistics and content metrics

### 🔍 SEO & Discovery
- **SEO Optimization**: Schema.org markup, meta tags, Open Graph support
- **Sitemap Generation**: Automatic XML sitemap for search engines
- **URL Structure**: SEO-friendly URLs for manga and chapters
- **Social Sharing**: Optimized social media integration

## Requirements

- **PHP**: 7.3 or higher
- **Laravel**: 6.x, 7.x, or 8.x
- **MySQL**: 5.7 or higher
- **Extensions**: GD or Imagick for image processing
- **Storage**: Sufficient space for manga images (recommended: SSD with CDN)

## Installation

### Step 1: Install Package

```bash
composer require jhin1m/mangacore -W
```

### Step 2: Publish and Run Migrations

```bash
# Publish package assets and config
php artisan vendor:publish --provider="Ophim\Core\OphimServiceProvider"

# Run database migrations
php artisan migrate
```

### Step 3: Install MangaCore

```bash
# Run the installation command
php artisan ophim:install

# Create admin user
php artisan ophim:user
```

### Step 4: Configure Environment

Add these settings to your `.env` file:

```env
# Basic Configuration
APP_URL=https://your-manga-site.com
APP_TIMEZONE=Asia/Ho_Chi_Minh

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manga_core
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Image Storage (optional CDN configuration)
MANGA_CDN_URL=https://cdn.your-site.com
MANGA_IMAGE_QUALITY=medium
MANGA_ENABLE_WEBP=true

# Cache Configuration (recommended)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Step 5: Configure Web Server

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-manga-site.com;
    root /path/to/your/project/public;
    index index.php;

    # Optimize for manga images
    location ~* \.(jpg|jpeg|png|gif|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-manga-site.com
    DocumentRoot /path/to/your/project/public

    # Optimize for manga images
    <LocationMatch "\.(jpg|jpeg|png|gif|webp)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public, immutable"
        Header append Vary Accept
    </LocationMatch>

    # Enable mod_rewrite for Laravel
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Configuration

### Basic Configuration

The main configuration file is located at `config/ophim.php` after publishing:

```php
return [
    // Site Information
    'site_name' => 'Your Manga Site',
    'site_description' => 'Read manga online',
    
    // Reading Settings
    'default_reading_mode' => 'single',
    'enable_guest_reading' => true,
    'preload_pages' => 3,
    
    // Image Settings
    'image_quality' => 'medium', // low, medium, high
    'enable_webp' => true,
    'thumbnail_size' => [200, 300],
    
    // CDN Settings
    'cdn_url' => env('MANGA_CDN_URL'),
    'enable_cdn' => !empty(env('MANGA_CDN_URL')),
    
    // Cache Settings
    'cache_duration' => 3600, // 1 hour
    'enable_page_cache' => true,
];
```

### Theme Configuration

MangaCore supports custom themes. Create a theme package or customize the default theme:

```php
// In your theme service provider
public function boot()
{
    $this->loadViewsFrom(__DIR__.'/../resources/views', 'manga-theme');
    $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
}
```

### Advanced Configuration

#### Image Processing

```php
// config/ophim.php
'image_processing' => [
    'driver' => 'gd', // gd or imagick
    'quality' => [
        'low' => 60,
        'medium' => 80,
        'high' => 95,
    ],
    'formats' => ['jpg', 'png', 'webp'],
    'max_width' => 1200,
    'max_height' => 1800,
],
```

#### Reading Experience

```php
// config/ophim.php
'reader' => [
    'modes' => ['single', 'double', 'vertical', 'horizontal'],
    'default_mode' => 'single',
    'keyboard_shortcuts' => true,
    'touch_gestures' => true,
    'preload_buffer' => 3,
    'auto_bookmark' => true,
],
```

## Usage

### Adding Manga Content

#### Via Admin Interface

1. Access admin panel at `/admin`
2. Navigate to "Manga" section
3. Click "Add New Manga"
4. Fill in manga details (title, description, cover image, etc.)
5. Add authors, artists, categories, and tags
6. Save manga entry

#### Via API

```php
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

// Create manga
$manga = Manga::create([
    'title' => 'One Piece',
    'original_title' => 'ワンピース',
    'description' => 'Epic pirate adventure manga',
    'type' => 'manga',
    'status' => 'ongoing',
    'demographic' => 'shounen',
    'reading_direction' => 'rtl',
]);

// Add chapter with pages
$chapter = $manga->chapters()->create([
    'title' => 'Romance Dawn',
    'chapter_number' => 1,
    'published_at' => now(),
]);

// Add pages to chapter
foreach ($pageImages as $index => $imagePath) {
    $chapter->pages()->create([
        'page_number' => $index + 1,
        'image_url' => $imagePath,
    ]);
}
```

### Batch Operations

#### Import Chapters from ZIP

```bash
# Import chapter from ZIP file
php artisan manga:import-chapter {manga_id} {zip_file_path}

# Example
php artisan manga:import-chapter 1 /path/to/chapter-001.zip
```

#### Optimize Images

```bash
# Optimize all images
php artisan manga:optimize-images

# Optimize specific manga
php artisan manga:optimize-images --manga=1

# Generate missing thumbnails
php artisan manga:generate-thumbnails
```

#### Cache Management

```bash
# Clear manga cache
php artisan manga:clean-cache

# Rebuild cache
php artisan manga:rebuild-cache
```

### Reading Progress API

```javascript
// Save reading progress
fetch('/api/reading-progress', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        manga_id: 1,
        chapter_id: 5,
        page_number: 10
    })
});

// Get reading progress
fetch('/api/reading-progress/1')
    .then(response => response.json())
    .then(data => {
        console.log('Current progress:', data);
    });
```

## API Reference

### Manga Endpoints

```
GET    /api/manga              # List manga with pagination
GET    /api/manga/{id}         # Get manga details
GET    /api/manga/{id}/chapters # Get manga chapters
POST   /api/manga              # Create manga (admin)
PUT    /api/manga/{id}         # Update manga (admin)
DELETE /api/manga/{id}         # Delete manga (admin)
```

### Chapter Endpoints

```
GET    /api/chapters/{id}      # Get chapter with pages
GET    /api/chapters/{id}/pages # Get chapter pages only
POST   /api/chapters           # Create chapter (admin)
PUT    /api/chapters/{id}      # Update chapter (admin)
DELETE /api/chapters/{id}      # Delete chapter (admin)
```

### Reading Progress Endpoints

```
GET    /api/reading-progress/{manga_id}  # Get user progress
POST   /api/reading-progress             # Save progress
DELETE /api/reading-progress/{manga_id}  # Clear progress
```

## Customization

### Custom Reading Modes

```javascript
// Extend the manga reader
class CustomMangaReader extends MangaReader {
    constructor(options) {
        super(options);
        this.addCustomMode('webtoon', this.webtoonMode);
    }
    
    webtoonMode() {
        // Custom webtoon reading implementation
        this.container.classList.add('webtoon-mode');
        this.enableVerticalScroll();
    }
}
```

### Custom Image Processing

```php
use Ophim\Core\Services\ImageProcessor;

class CustomImageProcessor extends ImageProcessor
{
    public function processImage($imagePath, $options = [])
    {
        // Custom image processing logic
        $processed = parent::processImage($imagePath, $options);
        
        // Add watermark, custom compression, etc.
        return $this->addCustomEffects($processed);
    }
}
```

### Theme Development

Create a custom theme by extending the base theme:

```php
// In your theme package
class MangaThemeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register theme views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'manga-theme');
        
        // Register theme routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // Publish theme assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('themes/manga-theme'),
        ], 'manga-theme-assets');
    }
}
```

## Troubleshooting

### Common Issues

#### Images Not Loading

**Problem**: Manga pages not displaying or loading slowly

**Solutions**:
1. Check file permissions on storage directories
2. Verify CDN configuration if using external storage
3. Ensure image optimization is working properly
4. Check web server configuration for static file serving

```bash
# Fix file permissions
chmod -R 755 storage/
chmod -R 755 public/

# Test image optimization
php artisan manga:optimize-images --test
```

#### Memory Issues During Upload

**Problem**: PHP memory limit exceeded when uploading large ZIP files

**Solutions**:
1. Increase PHP memory limit in `php.ini`
2. Use chunked upload for large files
3. Process images in batches

```ini
; php.ini
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
max_input_vars = 100000
```

#### Slow Page Loading

**Problem**: Manga pages load slowly or timeout

**Solutions**:
1. Enable Redis caching
2. Configure CDN for image delivery
3. Optimize database queries
4. Enable image lazy loading

```bash
# Enable Redis caching
composer require predis/predis
php artisan config:cache
```

#### Database Migration Errors

**Problem**: Migration fails during installation

**Solutions**:
1. Check database connection settings
2. Ensure MySQL version compatibility
3. Verify user permissions

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check migration status
php artisan migrate:status
```

### Performance Optimization

#### Database Optimization

```sql
-- Add custom indexes for better performance
CREATE INDEX idx_manga_search ON mangas(title, status, type);
CREATE INDEX idx_chapter_reading ON chapters(manga_id, chapter_number);
CREATE INDEX idx_reading_progress ON reading_progress(user_id, manga_id, updated_at);
```

#### Caching Strategy

```php
// config/cache.php
'stores' => [
    'manga' => [
        'driver' => 'redis',
        'connection' => 'manga',
        'prefix' => 'manga_cache',
    ],
],
```

#### Image Optimization

```bash
# Install additional image optimization tools
sudo apt-get install jpegoptim optipng pngquant gifsicle

# Configure automatic optimization
php artisan manga:optimize-images --schedule
```

## FAQ

### General Questions

**Q: Can I migrate from OphimCore (movie CMS) to MangaCore?**
A: Yes, MangaCore includes migration tools to convert movie data to manga format. However, you'll need to manually add manga-specific content like pages and chapters.

**Q: Does MangaCore support multiple languages?**
A: Yes, MangaCore supports Laravel's localization system. You can add language files for different locales.

**Q: Can I use MangaCore with existing Laravel applications?**
A: Yes, MangaCore is designed as a Laravel package and can be integrated into existing applications.

### Technical Questions

**Q: What image formats are supported?**
A: MangaCore supports JPG, PNG, GIF, and WebP formats. It automatically converts images to WebP when possible for better performance.

**Q: How do I backup manga data?**
A: Use Laravel's standard backup procedures for database and storage. Consider using cloud storage for image backups.

**Q: Can I customize the reading interface?**
A: Yes, the reading interface is built with JavaScript and can be customized or extended. You can also create custom themes.

**Q: How do I handle DMCA requests?**
A: MangaCore includes admin tools for content management. You can quickly remove content through the admin interface or API.

### Development Questions

**Q: How do I contribute to MangaCore development?**
A: Fork the repository, make your changes, and submit a pull request. Please follow the coding standards and include tests.

**Q: Can I create commercial themes for MangaCore?**
A: Yes, MangaCore's theme system supports commercial theme development. Check the license for specific terms.

**Q: How do I report bugs or request features?**
A: Use the GitHub issue tracker to report bugs or request features. Please provide detailed information and steps to reproduce.

## Support

- **Documentation**: [https://github.com/hacoidev/manga-core/wiki](https://github.com/hacoidev/manga-core/wiki)
- **Issues**: [https://github.com/hacoidev/manga-core/issues](https://github.com/hacoidev/manga-core/issues)
- **Discussions**: [https://github.com/hacoidev/manga-core/discussions](https://github.com/hacoidev/manga-core/discussions)

## License

MangaCore is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- Built on [Laravel Framework](https://laravel.com)
- Admin interface powered by [Backpack for Laravel](https://backpackforlaravel.com)
- Image processing with [Intervention Image](http://image.intervention.io)
- Caching system by [hacoidev/laravel-caching-model](https://github.com/hacoidev/laravel-caching-model)

---

**MangaCore** - Transforming manga reading experiences with modern web technology.