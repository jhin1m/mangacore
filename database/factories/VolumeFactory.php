<?php

namespace Database\Factories;

use Ophim\Core\Models\Volume;
use Ophim\Core\Models\Manga;
use Illuminate\Database\Eloquent\Factories\Factory;

class VolumeFactory extends Factory
{
    protected $model = Volume::class;

    public function definition()
    {
        return [
            'manga_id' => Manga::factory(),
            'volume_number' => $this->faker->numberBetween(1, 50),
            'title' => $this->faker->optional(0.7)->sentence(3),
            'published_at' => $this->faker->optional(0.8)->dateTimeBetween('-2 years', 'now'),
            'chapter_count' => $this->faker->numberBetween(1, 12),
        ];
    }

    /**
     * Create a published volume
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
     * Create an upcoming volume
     */
    public function upcoming()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => $this->faker->dateTimeBetween('now', '+6 months'),
            ];
        });
    }

    /**
     * Create a volume with specific number
     */
    public function withNumber($number)
    {
        return $this->state(function (array $attributes) use ($number) {
            return [
                'volume_number' => $number,
            ];
        });
    }

    /**
     * Create a volume with title
     */
    public function withTitle($title = null)
    {
        return $this->state(function (array $attributes) use ($title) {
            return [
                'title' => $title ?: $this->faker->sentence(3),
            ];
        });
    }
}