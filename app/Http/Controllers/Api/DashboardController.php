<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Post;
use App\Models\Article;

class DashboardController extends BaseController
{
    public function index()
    {
        $total_categories = Category::count();
        $total_posts = Post::count();
        $total_articles = Article::count();

        $categories = Category::withCount(['posts', 'articles'])
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $data = [
            'total_categories' => $total_categories,
            'total_posts' => $total_posts,
            'total_articles' => $total_articles,
            'categories' => $categories,
        ];

        return $this->handleResponse($data, 'Dashboard data');
    }
}
