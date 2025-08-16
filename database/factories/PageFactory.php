<?php

namespace Database\Factories;

use Ophim\Core\Models\Page;
use Ophim\Core\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition()
    {
        return [
            'chapter_id' => Chapter::factory(),
            'page_number' => $this->faker->numberBetween(1, 30),
            'image_url' => $this->generateImageUrl(),
        ];
    }

    /**
     * Generate a realistic image URL
     */
    protected function generateImageUrl()
    {
        $formats = ['jpg', 'jpeg', 'png', 'webp'];
        $format = $this->faker->randomElement($formats);
        
        // Generate different types of URLs
        $urlTypes = [
            // Local storage URLs
            '/storage/manga/chapters/' . $this->faker->uuid . '.' . $format,
            // CDN URLs
            'https://cdn.example.com/manga/' . $this->faker->uuid . '.' . $format,
            // External image URLs
            'https://images.example.com/manga/' . $this->faker->numberBetween(1000, 9999) . '.' . $format,
        ];
        
        return $this->faker->randomElement($urlTypes);
    }

    /**
     * Create a page with specific page number
     */
    public function withPageNumber($number)
    {
        return $this->state(function (array $attributes) use ($number) {
            return [
                'page_number' => $number,
            ];
        });
    }

    /**
     * Create a page with local image URL
     */
    public function localImage()
    {
        return $this->state(function (array $attributes) {
            $formats = ['jpg', 'jpeg', 'png', 'webp'];
            $format = $this->faker->randomElement($formats);
            
            return [
                'image_url' => '/storage/manga/chapters/' . $this->faker->uuid . '.' . $format,
            ];
        });
    }

    /**
     * Create a page with CDN image URL
     */
    public function cdnImage()
    {
        return $this->state(function (array $attributes) {
            $formats = ['jpg', 'jpeg', 'png', 'webp'];
            $format = $this->faker->randomElement($formats);
            
            return [
                'image_url' => 'https://cdn.example.com/manga/' . $this->faker->uuid . '.' . $format,
            ];
        });
    }

    /**
     * Create a page with WebP image
     */
    public function webpImage()
    {
        return $this->state(function (array $attributes) {
            return [
                'image_url' => '/storage/manga/chapters/' . $this->faker->uuid . '.webp',
            ];
        });
    }

    /**
     * Create sequential pages for a chapter
     */
    public function sequence($startPage = 1)
    {
        static $pageCounter = 0;
        $pageCounter = $startPage > 1 ? $startPage - 1 : $pageCounter;
        
        return $this->state(function (array $attributes) use (&$pageCounter) {
            return [
                'page_number' => ++$pageCounter,
            ];
        });
    }
}