# MangaCore Installation Guide

This guide provides detailed step-by-step instructions for installing and configuring MangaCore in various environments.

## Prerequisites

Before installing MangaCore, ensure your system meets the following requirements:

### System Requirements

- **Operating System**: Linux (Ubuntu 18.04+, CentOS 7+), macOS, or Windows
- **Web Server**: Nginx (recommended) or Apache 2.4+
- **PHP**: 7.3, 7.4, 8.0, or 8.1
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: Minimum 512MB RAM (2GB+ recommended for production)
- **Storage**: SSD recommended for image storage

### PHP Extensions

Ensure the following PHP extensions are installed:

```bash
# Ubuntu/Debian
sudo apt-get install php-cli php-fpm php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath

# CentOS/RHEL
sudo yum install php-cli php-fpm php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath

# macOS (using Homebrew)
brew install php@7.4
brew install php@7.4-gd php@7.4-mysql
```

### Composer

Install Composer globally:

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

### Node.js and NPM (for asset compilation)

```bash
# Ubuntu/Debian
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# CentOS/RHEL
curl -fsSL https://rpm.nodesource.com/setup_16.x | sudo bash -
sudo yum install -y nodejs

# macOS
brew install node

# Verify installation
node --version
npm --version
```

## Installation Methods

### Method 1: New Laravel Project (Recommended)

#### Step 1: Create New Laravel Project

```bash
# Create new Laravel project
composer create-project laravel/laravel manga-site "8.*"
cd manga-site

# Or for Laravel 7
composer create-project laravel/laravel manga-site "7.*"
```

#### Step 2: Install MangaCore Package

```bash
# Install MangaCore package
composer require jhin1m/mangacore -W

# Install additional dependencies
composer require hacoidev/laravel-caching-model
composer require hacoidev/crud
composer require hacoidev/settings
```

#### Step 3: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file with your configuration:

```env
APP_NAME="Your Manga Site"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-manga-site.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manga_core
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# MangaCore specific settings
MANGA_CDN_URL=https://cdn.your-site.com
MANGA_IMAGE_QUALITY=medium
MANGA_ENABLE_WEBP=true
```

#### Step 4: Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE manga_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'manga_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON manga_core.* TO 'manga_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate

# Publish package assets
php artisan vendor:publish --provider="Ophim\Core\OphimServiceProvider"
```

#### Step 5: Install MangaCore

```bash
# Run MangaCore installation
php artisan ophim:install

# Create admin user
php artisan ophim:user
```

#### Step 6: Compile Assets

```bash
# Install NPM dependencies
npm install

# Compile assets for production
npm run production

# Or for development
npm run dev
```

### Method 2: Existing Laravel Project

#### Step 1: Install Package

```bash
# Navigate to your Laravel project
cd /path/to/your/laravel/project

# Install MangaCore
composer require jhin1m/mangacore -W
```

#### Step 2: Publish and Configure

```bash
# Publish package files
php artisan vendor:publish --provider="Ophim\Core\OphimServiceProvider"

# Update your .env file with MangaCore settings
# (See configuration section above)

# Run migrations
php artisan migrate
```

#### Step 3: Update Routes (if needed)

Add to your `routes/web.php`:

```php
// MangaCore routes are automatically registered
// You can customize or override them here if needed
```

#### Step 4: Complete Installation

```bash
# Run installation command
php artisan ophim:install

# Create admin user
php artisan ophim:user
```

## Server Configuration

### Nginx Configuration

Create a new site configuration:

```bash
sudo nano /etc/nginx/sites-available/manga-site
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-manga-site.com www.your-manga-site.com;
    root /var/www/manga-site/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # Image optimization for manga
    location ~* \.(jpg|jpeg|png|gif|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept;
        
        # Enable WebP serving
        location ~* \.(jpe?g|png)$ {
            add_header Vary Accept;
            try_files $uri$webp_suffix $uri =404;
        }
    }

    # CSS and JS caching
    location ~* \.(css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Block access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.(?:htaccess|htpasswd|ini|log|sh|inc|bak)$ {
        deny all;
    }

    # Admin area protection (optional)
    location /admin {
        # Add IP whitelist if needed
        # allow 192.168.1.0/24;
        # deny all;
        
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/manga-site /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

Create a virtual host:

```bash
sudo nano /etc/apache2/sites-available/manga-site.conf
```

```apache
<VirtualHost *:80>
    ServerName your-manga-site.com
    ServerAlias www.your-manga-site.com
    DocumentRoot /var/www/manga-site/public

    # Security headers
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"

    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
    </IfModule>

    # Image caching for manga
    <LocationMatch "\.(jpg|jpeg|png|gif|webp)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public, immutable"
        Header append Vary Accept
    </LocationMatch>

    # CSS and JS caching
    <LocationMatch "\.(css|js)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public, immutable"
    </LocationMatch>

    # Laravel directory configuration
    <Directory /var/www/manga-site/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Error and access logs
    ErrorLog ${APACHE_LOG_DIR}/manga-site_error.log
    CustomLog ${APACHE_LOG_DIR}/manga-site_access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite manga-site.conf
sudo a2enmod rewrite headers expires deflate
sudo systemctl reload apache2
```

## Database Configuration

### MySQL Optimization for MangaCore

Edit MySQL configuration:

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add these optimizations:

```ini
[mysqld]
# Basic settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query cache (for MySQL 5.7)
query_cache_type = 1
query_cache_size = 128M

# Connection settings
max_connections = 200
wait_timeout = 600
interactive_timeout = 600

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Full-text search
ft_min_word_len = 2
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### Database Indexes

After installation, add custom indexes for better performance:

```sql
-- Connect to your database
mysql -u manga_user -p manga_core

-- Add performance indexes
CREATE INDEX idx_manga_search ON mangas(title(50), status, type);
CREATE INDEX idx_manga_popular ON mangas(view_count DESC, updated_at DESC);
CREATE INDEX idx_chapter_reading ON chapters(manga_id, chapter_number);
CREATE INDEX idx_chapter_latest ON chapters(manga_id, published_at DESC);
CREATE INDEX idx_reading_progress_user ON reading_progress(user_id, updated_at DESC);
CREATE INDEX idx_pages_chapter ON pages(chapter_id, page_number);

-- Full-text search indexes
ALTER TABLE mangas ADD FULLTEXT(title, original_title, other_name, description);
```

## Redis Configuration

### Install Redis

```bash
# Ubuntu/Debian
sudo apt-get install redis-server

# CentOS/RHEL
sudo yum install redis

# macOS
brew install redis
```

### Configure Redis

Edit Redis configuration:

```bash
sudo nano /etc/redis/redis.conf
```

Key settings for MangaCore:

```ini
# Memory settings
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password

# Network
bind 127.0.0.1
port 6379
```

Update Laravel configuration:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## SSL Configuration

### Using Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d your-manga-site.com -d www.your-manga-site.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Manual SSL Configuration

If using custom SSL certificates, update your Nginx configuration:

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-manga-site.com www.your-manga-site.com;

    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Rest of your configuration...
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name your-manga-site.com www.your-manga-site.com;
    return 301 https://$server_name$request_uri;
}
```

## Performance Optimization

### PHP-FPM Configuration

Edit PHP-FPM pool configuration:

```bash
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
```

Optimize for MangaCore:

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; PHP settings for image processing
php_admin_value[memory_limit] = 512M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_vars] = 100000
```

### OPcache Configuration

Edit PHP configuration:

```bash
sudo nano /etc/php/7.4/fpm/php.ini
```

Enable OPcache:

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=12
opcache.max_accelerated_files=60000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

## CDN Setup

### CloudFlare Configuration

1. Add your domain to CloudFlare
2. Configure DNS settings
3. Enable these optimizations:
   - Auto Minify (CSS, JS, HTML)
   - Brotli compression
   - Image optimization
   - Caching rules for images

### Amazon CloudFront

Create a CloudFront distribution:

```json
{
  "Origins": [
    {
      "DomainName": "your-manga-site.com",
      "Id": "manga-origin",
      "CustomOriginConfig": {
        "HTTPPort": 80,
        "HTTPSPort": 443,
        "OriginProtocolPolicy": "https-only"
      }
    }
  ],
  "DefaultCacheBehavior": {
    "TargetOriginId": "manga-origin",
    "ViewerProtocolPolicy": "redirect-to-https",
    "CachePolicyId": "4135ea2d-6df8-44a3-9df3-4b5a84be39ad"
  },
  "CacheBehaviors": [
    {
      "PathPattern": "*.jpg",
      "TargetOriginId": "manga-origin",
      "ViewerProtocolPolicy": "https-only",
      "CachePolicyId": "658327ea-f89d-4fab-a63d-7e88639e58f6"
    }
  ]
}
```

Update your `.env`:

```env
MANGA_CDN_URL=https://your-cloudfront-domain.cloudfront.net
```

## Monitoring and Maintenance

### Log Configuration

Configure log rotation:

```bash
sudo nano /etc/logrotate.d/manga-site
```

```
/var/www/manga-site/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
    postrotate
        /usr/bin/systemctl reload php7.4-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Cron Jobs

Add to crontab:

```bash
crontab -e
```

```bash
# Laravel scheduler
* * * * * cd /var/www/manga-site && php artisan schedule:run >> /dev/null 2>&1

# Daily image optimization
0 2 * * * cd /var/www/manga-site && php artisan manga:optimize-images >> /dev/null 2>&1

# Weekly cache cleanup
0 3 * * 0 cd /var/www/manga-site && php artisan manga:clean-cache >> /dev/null 2>&1

# Monthly sitemap generation
0 4 1 * * cd /var/www/manga-site && php artisan sitemap:generate >> /dev/null 2>&1
```

### Health Monitoring

Create a simple health check script:

```bash
nano /var/www/manga-site/health-check.sh
```

```bash
#!/bin/bash

# Check if application is responding
if curl -f -s http://localhost/health > /dev/null; then
    echo "Application: OK"
else
    echo "Application: FAILED"
    # Send alert or restart services
fi

# Check database connection
if php /var/www/manga-site/artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "Database: OK"
else
    echo "Database: FAILED"
fi

# Check Redis connection
if redis-cli ping > /dev/null 2>&1; then
    echo "Redis: OK"
else
    echo "Redis: FAILED"
fi
```

Make it executable and add to cron:

```bash
chmod +x /var/www/manga-site/health-check.sh

# Add to crontab
*/5 * * * * /var/www/manga-site/health-check.sh >> /var/log/manga-health.log 2>&1
```

## Troubleshooting Installation

### Common Installation Issues

#### Permission Errors

```bash
# Fix Laravel permissions
sudo chown -R www-data:www-data /var/www/manga-site
sudo chmod -R 755 /var/www/manga-site
sudo chmod -R 775 /var/www/manga-site/storage
sudo chmod -R 775 /var/www/manga-site/bootstrap/cache
```

#### Composer Memory Issues

```bash
# Increase Composer memory limit
php -d memory_limit=-1 /usr/local/bin/composer install
```

#### Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check MySQL service
sudo systemctl status mysql
sudo systemctl restart mysql
```

#### Asset Compilation Issues

```bash
# Clear NPM cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Try alternative compilation
npm run dev --verbose
```

### Getting Help

If you encounter issues during installation:

1. Check the [troubleshooting section](README.md#troubleshooting) in the main README
2. Search existing [GitHub issues](https://github.com/jhin1m/mangacore/issues)
3. Create a new issue with detailed error information
4. Join the [community discussions](https://github.com/jhin1m/mangacore/discussions)

## Next Steps

After successful installation:

1. **Configure your site**: Update site settings in the admin panel
2. **Add content**: Start adding manga, chapters, and pages
3. **Customize theme**: Install or create a custom theme
4. **Set up backups**: Configure automated backups for database and images
5. **Monitor performance**: Set up monitoring and alerting
6. **Security hardening**: Implement additional security measures

Congratulations! Your MangaCore installation is now complete and ready for use.