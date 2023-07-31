<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Post;
use App\Models\Upload;
use App\Traits\HasPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ArticleStatus;
use App\Models\Article;
use App\Models\Revision;
use App\Models\ArticleDetail;
use App\Models\RevisionDetail;

use Illuminate\Support\Facades\DB;



class UserController extends BaseController
{
    use HasPermission;

    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('read')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['name', 'created_at', 'updated_at'];
        $sort_by = $request->input('sort_by');
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $query = User::select('*');

        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        $users = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($users, 'users data');
    }

    public function create(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }
        $request->validate([
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'roles' => 'required|array',
            'meta_keys' => 'array',
            'meta_values' => 'array',
        ]);

        $user = new User;

        if ($request->upload_ids) {
            $user->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $roleIds = $request->roles;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();
        $user->roles()->sync($roleIds);
        if (!($request->has('meta_keys') && $request->has('meta_values'))) {
            return $this->handleResponse($user, 'User successfully created')->setStatusCode(201);
        }
        $meta_keys = $request->meta_keys;
        $meta_values = $request->meta_values;
        foreach ($meta_keys as $index => $key) {
            $user_meta = new UserMeta;
            $value = $meta_values[$index];
            $user_meta->user_id = $user->id;
            $user_meta->key = $key;
            $user_meta->value = $value;
            $user_meta->save();
        }
        return $this->handleResponse($user, 'User successfully created')->setStatusCode(201);
    }

    public function show(User $user)
    {
        if (!Auth::user()->hasPermission('read')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }
        $upload_ids = json_decode($user->upload_id, true);
        if ($upload_ids) {
            $user->uploads = DB::table('uploads')->whereIn('id', $upload_ids)->get();
        }
        return $this->handleResponse($user, 'User data details');
    }

    public function update(Request $request, User $user)
    {
        if (!$request->user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'name' => 'required|string|between:2,100',
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
            'roles' => 'required|array',
            'meta_keys' => 'array',
            'meta_values' => 'array',
        ]);

        $user->name = $request->name;
        $roleIds = $request->roles;
        $user->roles()->sync($roleIds);
        $user->password = bcrypt($request->new_password);
        if ($request->upload_ids) {
            $user->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $user->save();
        if (!($request->has('meta_keys') && $request->has('meta_values'))) {
            return $this->handleResponse($user, 'User successfully created')->setStatusCode(201);
        }
        $user_metas = $user->userMeta()->get();
        foreach ($user_metas as $user_meta) {
            $value = $user_meta->value;
            $user_meta->delete();
        }
        $meta_keys = $request->meta_keys;
        $meta_values = $request->meta_values;
        foreach ($meta_keys as $index => $key) {
            $user_meta = new UserMeta;
            $value = $meta_values[$index];
            $user_meta->user_id = $user->id;
            $user_meta->key = $key;
            $user_meta->value = $value;
            $user_meta->save();
        }
        return $this->handleResponse($user, 'User successfully updated')->setStatusCode(201);
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
        User::onlyTrashed()->whereIn('id', $ids)->restore();
        return $this->handleResponse([], 'User restored successfully!');
    }

    public function destroy(Request $request)
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
        $users = User::withTrashed()->whereIn('id', $ids)->get();

        foreach ($users as $user) {
            if ($type === 'force_delete') {
                $upload_ids = json_decode($user->upload_id, true);
                if ($upload_ids) {
                    $uploads = DB::table('uploads')->whereIn('id', $upload_ids)->get();
                }
                foreach ($uploads as $upload) {
                    Storage::delete($upload->path);
                    $upload->delete();
                }
                $user->forceDelete();
            } else {
                $user->delete();
            }
        }

        if ($type === 'force_delete') {
            return $this->handleResponse([], 'User force delete successfully!');
        } else {
            return $this->handleResponse([], 'User delete successfully!');
        }
    }

    public function manageFavorite(Request $request)
    {
        $request->validate([
            'type' => 'required|in:add,sub',
            'meta_data' => 'required|array',
            'meta_data.favorite_post' => 'required|array',
        ]);

        $user_id = Auth::id();
        $type = $request->type;
        $favorite_post = $request->meta_data['favorite_post'];
        $user_meta = UserMeta::where('user_id', $user_id)
            ->where('key', 'favorite_post')
            ->first();

        if ($type === 'add') {
            if ($user_meta) {
                $current_values = json_decode($user_meta->value, true);
                $new_values = array_values(array_unique(array_merge($current_values, $favorite_post)));
                $user_meta->value = json_encode($new_values);
                $user_meta->save();
            } else {
                $user_meta = new UserMeta;
                $user_meta->user_id = $user_id;
                $user_meta->key = 'favorite_post';
                $user_meta->value = json_encode($favorite_post);
                $user_meta->save();
            }

            return $this->handleResponse([], 'Add favorite successfully');
        }
        if ($type === 'sub') {
            if (!$user_meta) {
                return $this->handleResponse([], 'UserMeta record not found');
            }
            $stored_values = json_decode($user_meta->value, true);
            $updated_values = array_values(array_diff($stored_values, $favorite_post));

            $user_meta->value = json_encode($updated_values);
            $user_meta->save();

            return $this->handleResponse([], 'Value removed from favorites');
        }
    }

    public function showFavorite()
    {
        $user_meta = UserMeta::where('user_id', Auth::id())
            ->where('key', 'favorite_post')
            ->first();

        if (!$user_meta) {
            return $this->handleResponse([], 'Favorite posts not found for this user.');
        }
        $post_ids = json_decode($user_meta->value);
        $favorite_posts = Post::whereIn('id', $post_ids)->get();

        return $this->handleResponse($favorite_posts, 'Favorite posts of the user.');
    }

    public function approve(Request $request, Article $article)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'status' => 'required|string|in:published,reject',
            'reason' => 'string',
        ]);

        $author_email =  $article->user->email;

        if ($request->status === 'published') {
            $article->status = 'published';
            $article->published_at = now();
            $article->save();
            Mail::to($author_email)->send(new ArticleStatus($article, 'published'));
        }
        if ($request->status === 'reject') {
            Mail::to($author_email)->send(new ArticleStatus($article, 'reject', $request->reason));

            $article->status = 'pending';
            $article->save();
        }

        return $this->handleResponse($article, 'article status updated successfully');
    }

    public function approveRevision(Request $request, Revision $revision)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'status' => 'required|string|in:published,reject',
            'reason' => 'string',
        ]);

        $article = $revision->article;
        $revision = $article->revision()->latest()->first();
        $author_email =  $revision->user->email;
        $languages = config('app.languages');

        if ($request->status === 'reject') {
            // Mail::to($author_email)->send(new revisionStatus($revision, 'reject', $request->reason));
            return $this->handleResponse($revision, 'reject successfully');
        }

        $article->title = $revision->title;
        $article->description = $revision->description;
        $article->content = $revision->content;
        if ($revision->upload_id) {
            $article->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
            $article->upload_id = $revision->upload_id;
        }
        $article->save();
        foreach ($languages as $language) {
            $revisionDetail = $revision->revisionDetail->where('lang', $language)->first();
            $articleDetail = $article->articleDetail->where('lang', $language)->first();
            $articleDetail->title = $revisionDetail->title;
            $articleDetail->description = $revisionDetail->description;
            $articleDetail->content = $revisionDetail->content;
            $articleDetail->save();
        }
        $revisions = $article->revision()->where('version', '<', $revision->version);
        foreach ($revisions as $revision) {
            $upload_ids = json_decode($revision->upload_id, true);
            if ($upload_ids) {
                $uploads = Upload::whereIn('id', $upload_ids)->get();
            }
            foreach ($uploads as $upload) {
                Storage::delete($upload->path);
                $upload->delete();
            }
        }
        $article->revision()->delete();

        // Mail::to($author_email)->send(new revisionStatus($revision, 'published'));
        return $this->handleResponse([], 'published successfully');
    }
}
