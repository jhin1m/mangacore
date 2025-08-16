# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

OphimCore is a Laravel package that provides a content management system for movie/video streaming websites. It's structured as a Laravel service provider package with the following key components:

### Core Structure

- **Service Provider**: `src/OphimServiceProvider.php` - Main entry point that registers routes, views, policies, and configurations
- **Models**: Movie, Episode, Actor, Director, Category, Region, Studio, Tag, Catalog, Menu, Theme, User - representing the core entities
- **Controllers**: Admin CRUD controllers for managing content via Backpack/Laravel admin interface
- **Database**: Migrations and factories for all core entities with proper relationships
- **Views**: Blade templates for admin interface and theme system
- **Console Commands**: Artisan commands for installation, user creation, and maintenance

### Key Features

- **Movie Management**: Complete movie catalog with episodes, actors, directors, categories
- **Admin Interface**: Built on Backpack/Laravel for content management
- **Theme System**: Dynamic theme loading from vendor packages
- **SEO Integration**: Built-in SEO tools and sitemap generation
- **File Management**: CKFinder integration for media uploads
- **Scheduling**: Automated view counter resets (daily/weekly/monthly)

## Development Commands

### Laravel/PHP Commands

```bash
# Install the package (in host Laravel project)
composer require hacoidev/ophim-core -W

# Initial setup
php artisan ophim:install

# Create admin user
php artisan ophim:user

# Generate menu structure
php artisan ophim:menu:generate

# Change episode streaming domains
php artisan ophim:episode:change_domain

# Clear Laravel caches
php artisan optimize:clear
```

### Asset Building (Laravel Mix)

```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run prod
```

### Database Operations

```bash
# Run migrations
php artisan migrate

# Seed database with test data
php artisan db:seed --class="Database\Seeders\DatabaseSeeder"
```

## Configuration Requirements

### Environment Setup

- Laravel Framework 8+
- PHP 7.3+
- MySQL 5.7+
- Configure `php.ini`: `max_input_vars=100000`

### Key Configuration Files

- `config/config.php` - Package configuration for episodes, CKFinder
- `routes/admin.php` - Admin panel routes
- Database migrations in `database/migrations/`

### Production Environment

Set these in `.env`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- Configure timezone in `/config/app.php`: `'timezone' => 'Asia/Ho_Chi_Minh'`

## Package Dependencies

Core dependencies managed via Composer:

- `hacoidev/laravel-caching-model` - Model caching
- `hacoidev/crud` - Backpack CRUD operations
- `hacoidev/settings` - Settings management
- `hacoidev/permissionmanager` - User permissions
- `ckfinder/ckfinder-laravel-package` - File management
- `spatie/laravel-sitemap` - SEO sitemaps
- `artesaos/seotools` - SEO optimization

## Model Relationships

### Core Entities

- **Manga**: Central entity with many-to-many relationships to Author, Artist, Category, Origin, Publisher, Tag
- **Chapter**: Belongs to Manga, contains multiple pages with images
- **Page**: Belongs to Chapter, contains individual manga page images
- **Volume**: Organizes chapters into volumes
- **ReadingProgress**: Tracks user reading progress
- **Catalog**: Used for organizing manga into collections
- **Menu**: Dynamic menu system for frontend navigation
- **Theme**: Theme management and activation system

### Database Architecture

All models use Laravel Eloquent ORM with proper foreign key constraints and pivot tables for many-to-many relationships. Full-text search is enabled on mangas table.

## Maintenance

### Automated Tasks (Cron)

Set up crontab entry for scheduled tasks:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

This handles:

- Daily view counter resets
- Weekly view counter resets
- Monthly view counter resets

### Updates

```bash
composer update hacoidev/ophim-core -W
php artisan ophim:install
php artisan optimize:clear
```
