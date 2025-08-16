<?php

namespace Database\Factories;

use Ophim\Core\Models\ReadingProgress;
use Ophim\Core\Models\User;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingProgressFactory extends Factory
{
    protected $model = ReadingProgress::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'manga_id' => Manga::factory(),
            'chapter_id' => Chapter::factory(),
            'page_number' => $this->faker->numberBetween(1, 20),
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a reading progress for a guest user (no user_id).
     */
    public function guest()
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }

    /**
     * Create a completed reading progress (last page of chapter).
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            $chapter = Chapter::find($attributes['chapter_id']) ?? Chapter::factory()->create();
            return [
                'page_number' => $chapter->page_count ?? 20,
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Create a reading progress for a specific manga.
     */
    public function forManga(Manga $manga)
    {
        return $this->state(function (array $attributes) use ($manga) {
            $chapter = $manga->chapters()->inRandomOrder()->first() 
                ?? Chapter::factory()->create(['manga_id' => $manga->id]);
            
            return [
                'manga_id' => $manga->id,
                'chapter_id' => $chapter->id,
            ];
        });
    }

    /**
     * Create a reading progress for a specific user.
     */
    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Create a reading progress for a specific chapter.
     */
    public function forChapter(Chapter $chapter)
    {
        return $this->state(function (array $attributes) use ($chapter) {
            return [
                'manga_id' => $chapter->manga_id,
                'chapter_id' => $chapter->id,
                'page_number' => $this->faker->numberBetween(1, $chapter->page_count ?? 20),
            ];
        });
    }
}