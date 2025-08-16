# Installation Troubleshooting Guide

## Error: "Package hacoidev/ophim-core is not installed"

This error occurs during package installation. Here are the solutions:

### Solution 1: Install from GitHub directly (Recommended)

Since the package might not be on Packagist yet, install directly from GitHub:

```bash
# Method 1: Add to composer.json manually
```

Add this to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jhin1m/mangacore"
        }
    ],
    "require": {
        "jhin1m/mangacore": "^1.0"
    }
}
```

Then run:
```bash
composer install
```

### Solution 2: Install with specific version

```bash
composer require jhin1m/mangacore:^1.0 -W
```

### Solution 3: Force install from source

```bash
composer require jhin1m/mangacore --prefer-source -W
```

### Solution 4: Complete clean installation

If you're migrating from the old package:

```bash
# 1. Remove old package completely
composer remove hacoidev/ophim-core

# 2. Clear all caches
composer clear-cache
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 3. Remove vendor directory (nuclear option)
rm -rf vendor/
rm composer.lock

# 4. Add repository to composer.json
```

Add to your `composer.json`:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jhin1m/mangacore"
        }
    ]
}
```

```bash
# 5. Install fresh
composer install
composer require jhin1m/mangacore -W
```

## Alternative: Manual Installation

If Composer installation fails, you can install manually:

### Step 1: Download Package

```bash
# Clone the repository
git clone https://github.com/jhin1m/mangacore.git vendor/jhin1m/mangacore
```

### Step 2: Update Composer Autoload

Add to your project's `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Ophim\\Core\\": "vendor/jhin1m/mangacore/src/"
        }
    }
}
```

### Step 3: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // Other providers...
    Ophim\Core\OphimServiceProvider::class,
],
```

### Step 4: Install Dependencies

```bash
composer require hacoidev/laravel-caching-model
composer require hacoidev/crud
composer require hacoidev/settings
composer require hacoidev/permissionmanager
composer require ckfinder/ckfinder-laravel-package:v3.5.2.1
composer require spatie/laravel-sitemap:^5.8
composer require artesaos/seotools:^v0.22.1
```

### Step 5: Complete Installation

```bash
# Dump autoload
composer dump-autoload

# Publish assets
php artisan vendor:publish --provider="Ophim\Core\OphimServiceProvider"

# Run migrations
php artisan migrate

# Install MangaCore
php artisan ophim:install
```

## Verification

After installation, verify it works:

```bash
# Check if service provider is loaded
php artisan list | grep ophim

# You should see commands like:
# ophim:install
# ophim:user
# manga:import-chapter
# etc.
```

## Still Having Issues?

### Check Laravel Version Compatibility

MangaCore supports Laravel 6, 7, and 8. Check your Laravel version:

```bash
php artisan --version
```

### Check PHP Version

Ensure you have PHP 7.3 or higher:

```bash
php --version
```

### Check Required Extensions

```bash
php -m | grep -E "(gd|imagick|zip|curl|mbstring|xml)"
```

### Enable Debug Mode

Add to your `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Then try installation again and check `storage/logs/laravel.log` for detailed errors.

## Contact Support

If none of these solutions work:

1. Create an issue at: https://github.com/jhin1m/mangacore/issues
2. Include:
   - Your Laravel version
   - Your PHP version
   - Complete error message
   - Your composer.json content
   - Steps you've already tried

## Packagist Publication Status

**Note**: This package may not be published on Packagist yet. Use the GitHub installation method above until it's available on Packagist.

To check if it's on Packagist: https://packagist.org/packages/jhin1m/mangacore