<?php

namespace Ophim\Core\Database\Seeders;

use Backpack\Settings\app\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Menu;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Catalog;
use Ophim\Core\Models\Theme;

class MenusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $homeMenu = Menu::firstOrCreate(['name' => 'Trang chủ', 'link' => '/', 'type' => 'internal_link']);
        $categoryGroup = Menu::firstOrCreate(['name' => 'Thể loại', 'link' => '#', 'type' => 'internal_link']);
        $categories = Category::all();
        foreach ($categories as $category) {
            Menu::updateOrCreate([
                'name' => $category->name,
            ], [
                'link' => $category->getUrl(false),
                'type' => 'internal_link',
                'parent_id' => $categoryGroup->id
            ]);
        }

        // Legacy region menu - replaced with origin menu for manga system
        $originGroup = Menu::firstOrCreate(['name' => 'Xuất xứ', 'link' => '#', 'type' => 'internal_link']);
        $origins = Origin::all();
        foreach ($origins as $origin) {
            Menu::updateOrCreate([
                'name' => $origin->name,
            ], [
                'link' => $origin->getUrl(false),
                'type' => 'internal_link',
                'parent_id' => $originGroup->id
            ]);
        }
    }
}
