<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\ArticleDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;




class ArticleController extends BaseController
{
    public function index(Request $request)
    {
        $language = $request->input('language');
        $languages = config('app.languages');
        $language = in_array($language, $languages) ? $language : '';
        $status = $request->input('status');
        $layout_status = ['published', 'reject', 'pending'];
        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['title', 'created_at', 'updated_at'];
        $sort_by = $request->input('sort_by');
        $status = in_array($status, $layout_status) ? $status : 'published';
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $query = Article::select('*');

        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        if ($language) {
            $query = $query->whereHas('articleDetail', function ($q) use ($language) {
                $q->where('lang', $language);
            });
            $query = $query->with(['articleDetail' => function ($q) use ($language) {
                $q->where('lang', $language);
            }]);
        }
        $article = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($article, 'article data');
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'description' => 'string',
            'content' => 'string',
            'type' => 'string',
            'categories' => 'required|array',
        ]);

        $article = new article;
        $slug = Str::slug($request->title);
        $categoryIds = $request->categories;
        $user_id = Auth::id();
        $languages = config('app.languages');
        $seo_title = $request->title . ' - Duy';
        $seo_description = Str::limit($request->description, 160);

        if ($request->upload_ids) {
            $article->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $article->title = $request->title;
        $article->seo_title = $seo_title;
        $article->description = $request->description;
        $article->seo_description = $seo_description;
        $article->content = $request->content;
        $article->status = 'pending';
        $article->slug = $slug;
        $article->user_id = $user_id;
        $article->save();
        $article->category()->sync($categoryIds);
        foreach ($languages as $language) {
            $article_detail = new articleDetail;
            $article_detail->title = translate($request->title, $language);
            $article_detail->content = translate($request->content, $language);
            $article_detail->description = translate($request->description, $language);
            $article_detail->article_id = $article->id;
            $article_detail->lang = $language;
            $article_detail->save();
        }

        return $this->handleResponse($article, 'article created successfully');
    }

    public function show(Request $request, article $article)
    {
        $language = $request->language;
        if ($language) {
            $article->article_detail = $article->articleDetail()->where('lang', $language)->get();
        }
        $article->categories = $article->category()->where('status', 'active')->pluck('name');
        $upload_ids = json_decode($article->upload_id, true);
        if ($upload_ids) {
            $article->uploads = DB::table('uploads')->whereIn('id', $upload_ids)->get();
        }
        return $this->handleResponse($article, 'article data details');
    }

    public function update(Request $request, article $article)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }
        if ($article->status == 'published') {
            return $this->handleResponse([], 'Can not update published article');
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'description' => 'string',
            'type' => 'string',
            'categories' => 'required|array',
        ]);

        $slug = Str::slug($request->title);
        $categoryIds = $request->categories;
        $languages = config('app.languages');

        $article->title = $request->title;
        $article->content = $request->content;
        $article->type = $request->type;
        $article->slug = $slug;
        if ($request->upload_ids) {
            $article->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $article->category()->sync($categoryIds);
        $article->save();
        $article->articleDetail()->delete();
        foreach ($languages as $language) {
            $article_detail = new articleDetail;
            $article_detail->title = translate($request->title, $language);
            $article_detail->content = translate($request->content, $language);
            $article_detail->article_id = $article->id;
            $article_detail->lang = $language;
            $article_detail->save();
        }

        return $this->handleResponse($article, 'article updated successfully');
    }

    public function updateDetails(Request $request, article $article)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }
        if ($article->status == 'published') {
            return $this->handleResponse([], 'Can not update published article');
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'description' => 'string',

        ]);

        $language = $request->language;

        if (!($language && in_array($language, config('app.languages')))) {
            return $this->handleResponse([], 'Not Found Language');
        }
        $article_detail = $article->articleDetail()->where('lang', $language)->first();
        $article_detail->title = $request->title;
        $article_detail->content = $request->content;
        $article_detail->description = $request->description;
        $article_detail->save();

        return $this->handleResponse($article_detail, 'article detail updated successfully');
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
        article::onlyTrashed()->whereIn('id', $ids)->restore();
        foreach ($ids as $id) {
            $article = article::find($id);
            $article->status = 'pending';
            $article->save();
        }

        return $this->handleResponse([], 'article restored successfully!');
    }

    public function deleteArticle(Request $request)
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
        $articles = article::withTrashed()->whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            if ($type === 'force_delete') {
                $upload_ids = json_decode($article->upload_id, true);
                if ($upload_ids) {
                    $uploads = DB::table('uploads')->whereIn('id', $upload_ids)->get();
                }
                foreach ($uploads as $upload) {
                    Storage::delete($upload->path);
                    $upload->delete();
                }
                $article->forceDelete();
            } else {
                $article->status = 'archived';
                $article->save();
                $article->delete();
            }
        }

        if ($type === 'force_delete') {
            return $this->handleResponse([], 'article force delete successfully!');
        } else {
            return $this->handleResponse([], 'article delete successfully!');
        }
    }
}
