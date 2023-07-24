<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Category;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->word;

        return [
            'name' => $name,
            'description' => $this->faker->paragraph,
            'slug' => Str::slug($name),
            'type' => $this->faker->randomElement(['public', 'private']),
            'status' => $this->faker->randomElement(['active', 'deactive']),
            'author' => User::whereHas('roles', function ($query) {
                $query->whereIn('role_id', [1, 3]);
            })->inRandomOrder()->pluck('id')->first(),
        ];
    }
}
