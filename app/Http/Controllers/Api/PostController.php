<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PostController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $layout_status = ['draft', 'published', 'archived'];
        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['title', 'created_at', 'updated_at'];
        $sort_by = $request->input('sort_by');
        $status = in_array($status, $layout_status) ? $status : 'published';
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? 10;

        $query = Post::select('*');

        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        $posts = $query->orderBy($sort_by, $sort)->paginate($limit);

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
            'meta_keys' => 'array',
            'meta_values' => 'array',
        ]);

        $post = new Post;
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
        if ($request->has('meta_keys') && $request->has('meta_values')) {
            $metaKeys = $request->meta_keys;
            $metaValues = $request->meta_values;
            foreach ($metaKeys as $index => $metaKey) {
                $postMeta = new PostMeta;
                $value = $metaValues[$index];
                $postMeta->post_id = $post->id;
                $postMeta->key = $metaKey;
                if (is_file($value)) {
                    $imageName = Str::random(10);
                    $path = $value->storeAs('public/post/' . date('Y/m/d'), $imageName);
                    $postMeta->value = asset(Storage::url($path));
                } else {
                    $postMeta->value = $value;
                }
                $postMeta->save();
            }
        }

        $post->categories()->sync($categoryIds);

        return $this->handleResponse($post, 'Post created successfully');
    }


    public function show(Post $post)
    {
        $post->categories = $post->categories()->where('status', 'active')->pluck('name');
        $post->postMeta = $post->postMeta()->get();

        return $this->handleResponse($post, 'Post data details');
    }

    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'status' => 'in:draft,published,archived',
            'type' => 'string',
            'categories' => 'required|array',
            'meta_keys' => 'array',
            'meta_values' => 'array',
        ]);

        $value = $request->meta_value;
        $slug = Str::slug($request->title);
        $categoryIds = $request->categories;

        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->type = $request->type;
        $post->slug = $slug;
        $post->categories()->sync($categoryIds);
        $post->save();
        if ($request->has('meta_keys') && $request->has('meta_values')) {
            $postMetas = $post->postMeta()->get();
            foreach ($postMetas as $postMeta) {
                $value = $postMeta->value;
                if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                    $path = 'public' . Str::after($postMeta->value, 'storage');
                    Storage::delete($path);
                }
                $postMeta->delete();
            }
            $metaKeys = $request->meta_keys;
            $metaValues = $request->meta_values;
            foreach ($metaKeys as $index => $metaKey) {
                $postMeta = new PostMeta;
                $value = $metaValues[$index];
                $postMeta->post_id = $post->id;
                $postMeta->key = $metaKey;
                if (is_file($value)) {
                    $imageName = Str::random(10);
                    $path = $value->storeAs('public/post/' . date('Y/m/d'), $imageName);
                    $postMeta->value = asset(Storage::url($path));
                } else {
                    $postMeta->value = $value;
                }
                $postMeta->save();
            }
        }

        return $this->handleResponse($post, 'Post updated successfully');
    }

    public function destroy(Post $post)
    {
        $postMetas = $post->postMeta()->get();
        foreach ($postMetas as $postMeta) {
            $value = $postMeta->value;
            if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                $path = 'public' . Str::after($postMeta->value, 'storage');
                Storage::delete($path);
            }
        }
        $post->delete();

        return $this->handleResponse([], 'Post delete successfully!');
    }

    public function restore(Request $request)
    {
        $request->validate([
            'ids' => 'required',
        ]);

        $ids = $request->input('ids');

        $ids = is_array($ids) ? $ids : [$ids];
        Post::onlyTrashed()->whereIn('id', $ids)->restore();

        return $this->handleResponse([], 'Post restored successfully!');
    }

    public function forceDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required',
        ]);

        $ids = $request->input('ids');

        $ids = is_array($ids) ? $ids : [$ids];
        Post::withTrashed()->whereIn('id', $ids)->forceDelete();

        return $this->handleResponse([], 'Post force delete successfully!');
    }
}
