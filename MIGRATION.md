# Migration Guide: From hacoidev/ophim-core to jhin1m/mangacore

This guide helps you migrate from the old `hacoidev/ophim-core` package to the new `jhin1m/mangacore` package.

## Quick Migration Steps

### Step 1: Remove Old Package

```bash
# Remove the old package
composer remove hacoidev/ophim-core

# Clear composer cache
composer clear-cache
```

### Step 2: Install New Package

```bash
# Install the new package
composer require jhin1m/mangacore -W
```

### Step 3: Update Configuration (if needed)

If you have any custom configuration that references the old package name, update it:

```php
// Before (old)
'version' => \PackageVersions\Versions::getVersion('hacoidev/ophim-core')

// After (new)
'version' => \PackageVersions\Versions::getVersion('jhin1m/mangacore')
```

### Step 4: Clear Caches

```bash
# Clear all Laravel caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 5: Run Installation

```bash
# Run the installation command
php artisan ophim:install

# If you need to create a new admin user
php artisan ophim:user
```

## Troubleshooting

### Error: "Package hacoidev/ophim-core is not installed"

This error occurs when there are still references to the old package name in your code or configuration.

**Solution:**
1. Make sure you've completely removed the old package: `composer remove hacoidev/ophim-core`
2. Clear composer cache: `composer clear-cache`
3. Install the new package: `composer require jhin1m/mangacore -W`
4. Clear Laravel caches: `php artisan optimize:clear`

### Error: "Class not found" after migration

**Solution:**
1. Make sure autoloader is updated: `composer dump-autoload`
2. Clear all caches: `php artisan optimize:clear`
3. Check if all dependencies are properly installed: `composer install`

### Database Issues

The database schema remains the same, so no database migration is needed. However, if you encounter issues:

```bash
# Check migration status
php artisan migrate:status

# Run migrations if needed
php artisan migrate
```

## What Changed

### Package Information
- **Old**: `hacoidev/ophim-core`
- **New**: `jhin1m/mangacore`
- **Repository**: https://github.com/jhin1m/mangacore

### Dependencies
The following dependencies remain the same and are still required:
- `hacoidev/laravel-caching-model`
- `hacoidev/crud`
- `hacoidev/settings`
- `hacoidev/permissionmanager`

### Code Changes
- All core functionality remains the same
- Same namespace: `Ophim\Core\`
- Same service provider: `Ophim\Core\OphimServiceProvider`
- Same artisan commands

## Need Help?

If you encounter issues during migration:

1. Check the [troubleshooting section](README.md#troubleshooting)
2. Search [GitHub issues](https://github.com/jhin1m/mangacore/issues)
3. Create a new issue with detailed error information
4. Join [community discussions](https://github.com/jhin1m/mangacore/discussions)