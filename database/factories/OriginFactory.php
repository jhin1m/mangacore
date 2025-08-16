<?php

namespace Database\Factories;

use Ophim\Core\Models\Origin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OriginFactory extends Factory
{
    protected $model = Origin::class;

    public function definition()
    {
        $name = $this->faker->country();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'seo_title' => $name . ' - Xuất xứ manga',
            'seo_des' => 'Manga từ ' . $name,
            'seo_key' => $name . ', xuất xứ, manga',
            'user_id' => null,
            'user_name' => null,
        ];
    }
}