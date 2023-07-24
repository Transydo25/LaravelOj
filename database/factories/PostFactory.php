<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Post;
use App\Models\Category;
use App\Models\User;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence;
        return [
            'title' => $title,
            'content' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'type' => $this->faker->word,
            'slug' => Str::slug($title),
            'author' => User::inRandomOrder()->pluck('id')->first(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Post $post) {
            $categoryIds = Category::inRandomOrder()->pluck('id')->first();
            $post->categories()->sync($categoryIds);
        });
    }
}
