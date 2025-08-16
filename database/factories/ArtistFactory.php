<?php

namespace Database\Factories;

use Ophim\Core\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtistFactory extends Factory
{
    protected $model = Artist::class;

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
            'seo_title' => $name . ' - Họa sĩ manga',
            'seo_des' => 'Thông tin về họa sĩ ' . $name,
            'seo_key' => $name . ', họa sĩ, manga',
        ];
    }
}