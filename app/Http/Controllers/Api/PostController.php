<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\PostDetail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PostController extends BaseController
{
    public function index(Request $request)
    {
        $language = $request->input('language');
        $languages = config('app.languages');
        $language = in_array($language, $languages) ? $language : '';
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
        $limit = request()->input('limit') ?? config('app.paginate');
        $query = Post::select('*');

        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        if ($language) {
            $query = $query->whereHas('postDetail', function ($q) use ($language) {
                $q->where('lang', $language);
            });
            $query = $query->with(['postDetail' => function ($q) use ($language) {
                $q->where('lang', $language);
            }]);
        }
        $posts = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($posts, 'Posts data');
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

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
        $languages = config('app.languages');

        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->type = $request->type;
        $post->slug = $slug;
        $post->author = $user_id;
        $post->save();
        $post->categories()->sync($categoryIds);
        foreach ($languages as $language) {
            $post_detail = new PostDetail;
            $post_detail->title = translate($request->title, $language);
            $post_detail->content = translate($request->content, $language);
            $post_detail->post_id = $post->id;
            $post_detail->lang = $language;
            $post_detail->save();
        }
        if (!($request->has('meta_keys') && $request->has('meta_values'))) {
            return $this->handleResponse($post, 'Post created successfully');
        }
        $meta_keys = $request->meta_keys;
        $meta_values = $request->meta_values;
        foreach ($meta_keys as $index => $metaKey) {
            $post_meta = new PostMeta;
            $value = $meta_values[$index];
            $post_meta->post_id = $post->id;
            $post_meta->key = $metaKey;
            if (is_file($value)) {
                $image_name = Str::random(10);
                $path = $value->storeAs('public/posts/' . date('Y/m/d'), $image_name);
                $post_meta->value = asset(Storage::url($path));
            } else {
                $post_meta->value = $value;
            }
            $post_meta->save();
        }
        return $this->handleResponse($post, 'Post created successfully');
    }

    public function show(Request $request, Post $post)
    {
        $language = $request->language;
        if ($language) {
            $post->post_detail = $post->postDetail()->where('lang', $language)->get();
        }
        $post->categories = $post->categories()->where('status', 'active')->pluck('name');
        $post->post_meta = $post->postMeta()->get();

        return $this->handleResponse($post, 'Post data details');
    }


    public function updateDetails(Request $request, Post $post)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
        ]);

        $language = $request->language;

        if (!($language && in_array($language, config('app.languages')))) {
            return $this->handleResponse([], 'Not Found Language');
        }
        $post_detail = $post->postDetail()->where('lang', $language)->first();
        $post_detail->title = $request->title;
        $post_detail->content = $request->content;
        $post_detail->save();
        return $this->handleResponse($post_detail, 'Post detail updated successfully');
    }

    public function update(Request $request, Post $post)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

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
        $languages = config('app.languages');

        $post->title = $request->title;
        $post->content = $request->content;
        if ((Auth::user()->hasRole('editor') && Auth::id() == $post->author) || Auth::user()->hasRole('admin')) {
            $post->status = $request->status;
        }
        $post->type = $request->type;
        $post->slug = $slug;
        $post->categories()->sync($categoryIds);
        $post->save();
        $post->postDetail()->delete();
        foreach ($languages as $language) {
            $post_detail = new PostDetail;
            $post_detail->title = Translate::translate($request->title, $language);
            $post_detail->content = Translate::translate($request->content, $language);
            $post_detail->post_id = $post->id;
            $post_detail->lang = $language;
            $post_detail->save();
        }
        if ($request->has('meta_keys') && $request->has('meta_values')) {
            $post_metas = $post->postMeta()->get();
            foreach ($post_metas as $post_meta) {
                $value = $post_meta->value;
                if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                    $path = 'public' . Str::after($post_meta->value, 'storage');
                    Storage::delete($path);
                }
                $post_meta->delete();
            }
            $metaKeys = $request->meta_keys;
            $metaValues = $request->meta_values;
            foreach ($metaKeys as $index => $metaKey) {
                $post_meta = new PostMeta;
                $value = $metaValues[$index];
                $post_meta->post_id = $post->id;
                $post_meta->key = $metaKey;
                if (is_file($value)) {
                    $imageName = Str::random(10);
                    $path = $value->storeAs('public/posts/' . date('Y/m/d'), $imageName);
                    $post_meta->value = asset(Storage::url($path));
                } else {
                    $post_meta->value = $value;
                }
                $post_meta->save();
            }
        }

        return $this->handleResponse($post, 'Post updated successfully');
    }


    public function restore(Request $request)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'ids' => 'required',
        ]);

        $ids = $request->input('ids');

        $ids = is_array($ids) ? $ids : [$ids];
        Post::onlyTrashed()->whereIn('id', $ids)->restore();
        foreach ($ids as $id) {
            $post = Post::find($id);
            $post->status = 'published';
            $post->save();
        }

        return $this->handleResponse([], 'Post restored successfully!');
    }

    public function deletePost(Request $request)
    {
        if (!Auth::user()->hasPermission('delete')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'ids' => 'required',
            'type' => 'required|in:delete,force_delete',
        ]);

        $ids = $request->input('ids');
        $type = $request->input('type');
        $ids = is_array($ids) ? $ids : [$ids];
        $posts = Post::withTrashed()->whereIn('id', $ids)->get();

        foreach ($posts as $post) {
            if ($type === 'force_delete') {
                $post_metas = $post->postMeta()->get();
                foreach ($post_metas as $post_meta) {
                    $value = $post_meta->value;
                    if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                        $path = 'public' . Str::after($post_meta->value, 'storage');
                        Storage::delete($path);
                    }
                }
                $post->forceDelete();
            } else {
                $post->status = 'archived';
                $post->save();
                $post->delete();
            }
        }

        if ($type === 'force_delete') {
            return $this->handleResponse([], 'Post force delete successfully!');
        } else {
            return $this->handleResponse([], 'Post delete successfully!');
        }
    }
}
