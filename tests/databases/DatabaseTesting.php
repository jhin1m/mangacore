<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Ophim\Core\Database\Seeders\CategoriesTableSeeder;
use Ophim\Core\Database\Seeders\SettingsTableSeeder;
use Ophim\Core\Database\Seeders\RegionsTableSeeder;
use Ophim\Core\Database\Seeders\ThemesTableSeeder;
use Ophim\Core\Database\Seeders\MenusTableSeeder;
// Legacy models - deprecated, use manga-specific models instead
use Ophim\Core\Models\Actor;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Director;
use Ophim\Core\Models\Region;
use Ophim\Core\Models\Tag;
// Manga-specific models
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Publisher;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function test()
    {
        $this->call([
            CategoriesTableSeeder::class,
            RegionsTableSeeder::class,
            ThemesTableSeeder::class,
            MenusTableSeeder::class,
            SettingsTableSeeder::class,
        ]);

        // Legacy data generation (deprecated)
        Actor::factory(100)->create();
        Director::factory(100)->create();
        Tag::factory(100)->create();

        // Manga-specific data generation
        Author::factory(100)->create();
        Artist::factory(100)->create();
        Publisher::factory(50)->create();

        for ($i = 1; $i < 1000; $i++) {
            Manga::factory(1)
                ->state([
                    'publication_year' => rand(2018, 2022)
                ])
                ->hasAttached(Origin::all()->random())
                ->hasAttached(Category::all()->random(3))
                ->hasAttached(Author::all()->random(rand(1, 3)))
                ->hasAttached(Artist::all()->random(1))
                ->hasAttached(Publisher::all()->random(1))
                ->hasAttached(Tag::all()->random(5))
                ->has(Chapter::factory(rand(5, 50))->state([
                    'chapter_number' => function ($attributes, $manga) {
                        static $chapterNumbers = [];
                        if (!isset($chapterNumbers[$manga['id']])) {
                            $chapterNumbers[$manga['id']] = 1;
                        }
                        return $chapterNumbers[$manga['id']]++;
                    }
                ]))
                ->create();
        }
    }
}
