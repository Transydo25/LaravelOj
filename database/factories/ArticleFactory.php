<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\ArticleDetail;

class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence;
        $description = $this->faker->sentence;
        return [
            'title' => $title,
            'seo_title' => $title . ' -Duy',
            'description' => $description,
            'seo_description' => Str::limit($description, 160),
            'content' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['pending', 'published']),
            'slug' => Str::slug($title),
            'user_id' => User::whereHas('roles', function ($query) {
                $query->whereIn('role_id', [1, 3]);
            })->inRandomOrder()->pluck('id')->first(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Article $article) {
            $languages = config('app.languages');
            $category_ids = Category::inRandomOrder()->pluck('id')->first();
            $article->category()->sync($category_ids);
            foreach ($languages as $language) {
                $article_detail = new ArticleDetail;
                $article_detail->title = translate($article->title, $language);
                $article_detail->content = translate($article->content, $language);
                $article_detail->description = translate($article->description, $language);
                $article_detail->article_id = $article->id;
                $article_detail->lang = $language;
                $article_detail->save();
            }
        });
    }
}
