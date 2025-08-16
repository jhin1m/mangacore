<?php

namespace Database\Factories;

use Ophim\Core\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition()
    {
        $name = $this->faker->name();
        return [
            'name' => $name,
            'name_md5' => md5($name),
            'slug' => Str::slug($name),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'bio' => $this->faker->paragraph(),
            'thumb_url' => $this->faker->imageUrl(300, 300, 'people'),
            'seo_title' => $name . ' - Tác giả manga',
            'seo_des' => 'Thông tin về tác giả ' . $name,
            'seo_key' => $name . ', tác giả, manga',
        ];
    }
}