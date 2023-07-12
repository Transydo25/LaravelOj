<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;




class PostController extends BaseController
{
    public function index()
    {
        $posts = Post::all();
        return $this->handleResponse($posts, 'Posts data');
    }

    public function store(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'status' => 'in:draft,published,archived',
            'type' => 'string',
            'categories' => 'required|array',
        ]);

        $slug = Str::slug($request->title);
        $categoryIds = $request->categories;
        $user_id = Auth::id();

        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->type = $request->type;
        $post->slug = $slug;
        $post->author = $user_id;
        $post->save();
        foreach ($categoryIds as $categoryId) {
            $post->categories()->attach($categoryId);
        }

        return $this->handleResponse($post, 'Post created successfully');
    }


    public function show(Post $post)
    {
        $data = $post->load('categories:name');
        return $this->handleResponse($data, 'Post data');
    }

    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'status' => 'in:draft,published,archived',
            'type' => 'string',
            'categories' => 'required|array',
        ]);

        $slug = Str::slug($request->title);
        $categoryIds = $request->categories;
        $user_id = Auth::id();

        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->type = $request->type;
        $post->slug = $slug;
        $post->author = $user_id;
        $post->save();
        $post->categories()->detach();
        foreach ($categoryIds as $categoryId) {
            $post->categories()->attach($categoryId);
        }

        return $this->handleResponse($post, 'Post created successfully');
    }


    public function destroy(Post $post)
    {
        $post->delete();
        return $this->handleResponse([], 'Post delete successfully!');
    }
}
