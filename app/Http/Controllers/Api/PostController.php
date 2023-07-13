<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;




class PostController extends BaseController
{
    public function index()
    {
        $posts = Post::where('status', 'published')->get();
        return $this->handleResponse($posts, 'Posts data');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'status' => 'in:draft,published,archived',
            'type' => 'string',
            'categories' => 'required|array',
            'meta_key' => 'string',
        ]);

        $post = new Post;
        $value = $request->meta_value;
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
        if ($request->has('meta_key') && $request->has('meta_value')) {
            $post_meta = new PostMeta;
            $value = $request->meta_value;
            $post_meta->post_id = $post->id;
            $post_meta->key = $request->meta_key;
            if (is_file($value)) {
                $post_meta->type = 'file';
                $imageName = Str::random(10);
                $path = $value->storeAs('public/post/' . date('Y/m/d'), $imageName);
                $post_meta->value = asset(Storage::url($path));
            } else {
                $post_meta->type = 'string';
                $post_meta->value = $value;
            }
            $post_meta->save();
        }

        $post->categories()->attach($categoryIds);

        return $this->handleResponse($post, 'Post created successfully');
    }


    public function show(Post $post)
    {
        $data = $post->load([
            'categories' => function ($query) {
                $query->where('status', 'active');
            },
            'categories:name',
            'postMeta'
        ]);

        return $this->handleResponse($data, 'success');
    }

    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'status' => 'in:draft,published,archived',
            'type' => 'string',
            'categories' => 'required|array',
            'meta_key' => 'string',
        ]);

        $value = $request->meta_value;
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
        if ($request->has('meta_key') && $request->has('meta_value')) {
            $post_meta = new PostMeta;
            $value = $request->meta_value;
            $post_meta->post_id = $post->id;
            $post_meta->key = $request->meta_key;
            if (is_file($value)) {
                $post_meta->type = 'file';
                $imageName = Str::random(10);
                $path = $value->storeAs('public/post/' . date('Y/m/d'), $imageName);
                $post_meta->value = asset(Storage::url($path));
            } else {
                $post_meta->type = 'string';
                $post_meta->value = $value;
            }
            $post_meta->save();
        }
        $post->categories()->sync($categoryIds);

        return $this->handleResponse($post, 'Post updated successfully');
    }


    public function destroy(Post $post)
    {
        $user_id = Auth::id();

        if ($user_id !== $post->author) {
            return $this->handleError([], 'You are not authorized to deleted this post');
        }
        $post->forceDelete();

        return $this->handleResponse([], 'Post delete successfully!');
    }
}
