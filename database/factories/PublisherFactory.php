<?php

namespace Database\Factories;

use Ophim\Core\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition()
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'name_md5' => md5($name),
            'slug' => Str::slug($name),
            'thumb_url' => $this->faker->imageUrl(300, 300, 'business'),
            'seo_title' => $name . ' - Nhà xuất bản manga',
            'seo_des' => 'Thông tin về nhà xuất bản ' . $name,
            'seo_key' => $name . ', nhà xuất bản, manga',
        ];
    }
}