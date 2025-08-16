<?php

namespace Database\Factories;

use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition()
    {
        $chapterNumber = $this->faker->randomFloat(1, 1, 999);
        $title = $this->faker->optional(0.7)->sentence(3);
        
        return [
            'manga_id' => Manga::factory(),
            'volume_id' => $this->faker->optional(0.6)->randomElement([null, Volume::factory()]),
            'title' => $title,
            'slug' => $title ? Str::slug($title) : "chapter-{$chapterNumber}",
            'chapter_number' => $chapterNumber,
            'volume_number' => $this->faker->optional(0.6)->numberBetween(1, 50),
            'page_count' => $this->faker->numberBetween(15, 45),
            'view_count' => $this->faker->numberBetween(0, 10000),
            'published_at' => $this->faker->optional(0.9)->dateTimeBetween('-2 years', 'now'),
            'is_premium' => $this->faker->boolean(10), // 10% chance of being premium
        ];
    }

    /**
     * Indicate that the chapter is published
     */
    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }

    /**
     * Indicate that the chapter is unpublished
     */
    public function unpublished()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            ];
        });
    }

    /**
     * Indicate that the chapter is premium
     */
    public function premium()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_premium' => true,
            ];
        });
    }

    /**
     * Indicate that the chapter is free
     */
    public function free()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_premium' => false,
            ];
        });
    }

    /**
     * Create a chapter with a specific chapter number
     */
    public function withChapterNumber($number)
    {
        return $this->state(function (array $attributes) use ($number) {
            return [
                'chapter_number' => $number,
                'slug' => $attributes['title'] ? Str::slug($attributes['title']) : "chapter-{$number}",
            ];
        });
    }

    /**
     * Create a chapter with pages
     */
    public function withPages($count = null)
    {
        $pageCount = $count ?? $this->faker->numberBetween(15, 45);
        
        return $this->state(function (array $attributes) use ($pageCount) {
            return [
                'page_count' => $pageCount,
            ];
        })->afterCreating(function (Chapter $chapter) use ($pageCount) {
            // This will be implemented when Page model is created in task 5
            // Page::factory($pageCount)->create(['chapter_id' => $chapter->id]);
        });
    }
}