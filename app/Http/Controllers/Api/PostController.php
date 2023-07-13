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
        $posts = Post::where('status', 'published')->get();
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
        $post->categories()->attach($categoryIds);

        return $this->handleResponse($post, 'Post created successfully');
    }


    public function show(Post $post)
    {
        $activeCategories = $post->categories()->where('status', 'active')->pluck('name')->toArray();
        $post->setAttribute('categories', $activeCategories);

        return $this->handleResponse($post, 'Post data');
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

        if ($user_id !== $post->author) {
            return $this->handleResponseError([], 'You are not authorized to update this post');
        }
        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->type = $request->type;
        $post->slug = $slug;
        $post->save();
        $post->categories()->sync($categoryIds);

        return $this->handleResponse($post, 'Post updated successfully');
    }


    public function destroy(Post $post)
    {
        $user_id = Auth::id();

        if ($user_id !== $post->author) {
            return $this->handleError([], 'You are not authorized to deleted this post');
        }
        $post->delete();

        return $this->handleResponse([], 'Post delete successfully!');
    }
}
