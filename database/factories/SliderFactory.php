<?php

namespace Database\Factories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slider>
 */
class SliderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => $this->faker->imageUrl(1200, 400, 'business', true, 'slider', true, 'png'),
            'tagline' => $this->faker->sentence(3, true),
            'title' => $this->faker->sentence(4, true),
            'subtitle' => $this->faker->sentence(6, true),
            'link_url' => $this->faker->url(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'starts_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'ends_at' => $this->faker->dateTimeBetween('+2 weeks', '+1 month'),
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }
}
