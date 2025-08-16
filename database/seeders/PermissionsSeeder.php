<?php

namespace Ophim\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Backpack\PermissionManager\app\Models\Permission;
use Backpack\PermissionManager\app\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Movie-related permissions commented out - replaced by manga equivalents
            // 'Browse actor',      // Use 'Browse author' instead
            // 'Create actor',      // Use 'Create author' instead
            // 'Update actor',      // Use 'Update author' instead
            // 'Delete actor',      // Use 'Delete author' instead
            // 'Browse director',   // Use 'Browse artist' instead
            // 'Create director',   // Use 'Create artist' instead
            // 'Update director',   // Use 'Update artist' instead
            // 'Delete director',   // Use 'Delete artist' instead
            'Browse tag',
            'Create tag',
            'Update tag',
            'Delete tag',
            // 'Browse studio',     // Use 'Browse publisher' instead
            // 'Create studio',     // Use 'Create publisher' instead
            // 'Update studio',     // Use 'Update publisher' instead
            // 'Delete studio',     // Use 'Delete publisher' instead
            'Browse catalog',
            'Create catalog',
            'Update catalog',
            'Delete catalog',
            'Browse category',
            'Create category',
            'Update category',
            'Delete category',
            // 'Browse region',     // Use 'Browse origin' instead
            // 'Create region',     // Use 'Create origin' instead
            // 'Update region',     // Use 'Update origin' instead
            // 'Delete region',     // Use 'Delete origin' instead
            'Browse crawl schedule',
            'Create crawl schedule',
            'Update crawl schedule',
            'Delete crawl schedule',
            // Movie permissions commented out - replaced by manga equivalents
            // 'Browse movie',      // Use 'Browse manga' instead
            // 'Create movie',      // Use 'Create manga' instead
            // 'Update movie',      // Use 'Update manga' instead
            // 'Delete movie',      // Use 'Delete manga' instead
            'Browse user',
            'Create user',
            'Update user',
            'Delete user',
            'Browse role',
            'Create role',
            'Update role',
            'Delete role',
            'Browse permission',
            'Create permission',
            'Update permission',
            'Delete permission',
            // Episode permissions commented out - replaced by chapter equivalents
            // 'Browse episode',    // Use 'Browse chapter' instead
            // 'Create episode',    // Use 'Create chapter' instead
            // 'Update episode',    // Use 'Update chapter' instead
            // 'Delete episode',    // Use 'Delete chapter' instead
            'Browse menu',
            'Create menu',
            'Update menu',
            'Delete menu',
            'Delete menu item',
            'Browse plugin',
            'Update plugin',
            'Customize theme',
            
            // Manga-related permissions (new equivalents)
            'Browse manga',
            'Create manga',
            'Update manga',
            'Delete manga',
            'Browse chapter',
            'Create chapter',
            'Update chapter',
            'Delete chapter',
            'Browse author',
            'Create author',
            'Update author',
            'Delete author',
            'Browse artist',
            'Create artist',
            'Update artist',
            'Delete artist',
            'Browse publisher',
            'Create publisher',
            'Update publisher',
            'Delete publisher',
            'Browse origin',
            'Create origin',
            'Update origin',
            'Delete origin',
        ];

        $admin = Role::firstOrCreate(['name' => "Admin", 'guard_name' => 'backpack']);
        foreach ($permissions as $index => $permission) {
            $result = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'backpack'
            ]);

            $admin->givePermissionTo($permission);

            if (!$result) {
                $this->command->info("Insert failed at record $index.");

                return;
            }
        }
    }
}
