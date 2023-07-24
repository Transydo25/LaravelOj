<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Post;
use App\Models\Category;
use App\Models\User;
use App\Models\PostDetail;
use App\Models\PostMeta;

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
            'author' => User::whereHas('roles', function ($query) {
                $query->whereIn('role_id', [1, 3]);
            })->inRandomOrder()->pluck('id')->first(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Post $post) {
            $languages = config('app.languages');
            $categoryIds = Category::inRandomOrder()->pluck('id')->first();
            $post->categories()->sync($categoryIds);
            foreach ($languages as $language) {
                $post_detail = new PostDetail;
                $post_detail->title = translate($post->title, $language);
                $post_detail->content = translate($post->content, $language);
                $post_detail->post_id = $post->id;
                $post_detail->lang = $language;
                $post_detail->save();
            }
            $post_meta = new PostMeta;
            $post_meta->post_id = $post->id;
            $post_meta->key = $this->faker->word;
            $post_meta->value = $this->faker->sentence;
            $post_meta->save();
        });
    }
}
