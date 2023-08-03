<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Article;
use App\Models\RevisionArticle;
use App\Models\RevisionArticleDetail;
use App\Models\Upload;
use Illuminate\Support\Facades\Mail;
use App\Mail\ArticleStatus;


class RevisionArticleController extends BaseController
{

    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('update')) {
            return  $this->handleError('Unauthorized', 403);
        }

        $status = $request->input('status');
        $layout_status = ['published', 'pending'];
        $languages = config('app.languages');
        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['title', 'created_at', 'updated_at', 'article_id'];
        $sort_by = $request->input('sort_by');
        $status = in_array($status, $layout_status) ? $status : 'pending';
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $language = $request->language;
        $language = in_array($language, $languages) ? $language : '';
        $article_id = $request->article_id;
        $query = RevisionArticle::select('*');
        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($article_id) {
            $query = $query->where('article_id', $article_id);
        }
        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        if ($language) {
            $query = $query->with(['articleDetail' => function ($q) use ($language) {
                $q->where('language', $language);
            }]);
        }
        $revisionArticles = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($revisionArticles, 'Revision article data');
    }

    public function store(Request $request, Article $article)
    {
        if (!$request->user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $revision_article = new RevisionArticle;
        $languages = config('app.languages');

        $revision_article->article_id = $article->id;
        $revision_article->title = $article->title;
        $revision_article->description = $article->description;
        $revision_article->content = $article->content;
        $revision_article->upload_id = $article->upload_id;
        $revision_article->version = $article->revisionArticle()->where('article_id', $article->id)->count() + 1;
        $revision_article->user_id = $article->user_id;
        $revision_article->status = 'pending';
        $revision_article->category_ids = $article->category()->select('categories.id')->pluck('id');
        $revision_article->save();
        foreach ($languages as $language) {
            $revision_article_detail = new RevisionArticleDetail;
            $revision_article_detail->title = Translate($revision_article->title, $language);
            $revision_article_detail->content = Translate($revision_article->content, $language);
            $revision_article_detail->description = Translate($revision_article->description, $language);
            $revision_article_detail->revision_article_id = $revision_article->id;
            $revision_article_detail->lang = $language;
            $revision_article_detail->save();
        }

        return $this->handleResponse($revision_article, 'revisionArticle created successfully');
    }

    public function list(Request $request, Article $article)
    {
        $revision_articles = $article->revisionArticle()->where('article_id', $article->id)->get();
        $total_records = count($revision_articles);

        $response_data = [
            'data' => $revision_articles,
            'total_records' => $total_records,
        ];

        return $this->handleResponse($response_data, 'revision_articles data list');
    }

    public function show(Request $request, RevisionArticle $revision_article)
    {
        $language = $request->language;

        if ($language) {
            $revision_article->revision_article_detail = $revision_article->revisionArticleDetail()->where('lang', $language)->get();
        }
        $upload_ids = json_decode($revision_article->upload_id, true);
        if ($upload_ids) {
            $revision_article->uploads = Upload::whereIn('id', $upload_ids)->get();
        }

        return $this->handleResponse($revision_article, 'revision article data details');
    }


    public function update(Request $request, RevisionArticle $revision_article)
    {
        if (!$request->user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'description' => 'string',
            'category_ids' => 'array',
        ]);

        $languages = config('app.languages');

        if ($request->upload_ids) {
            $revision_article->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $revision_article->title = $request->title;
        $revision_article->description = $request->description;
        $revision_article->content = $request->content;
        $revision_article->category_ids = json_encode($request->category_ids);
        $revision_article->save();
        $revision_article->revisionArticleDetail()->delete();
        foreach ($languages as $language) {
            $revision_article_detail = new RevisionArticleDetail;
            $revision_article_detail->title = Translate($revision_article->title, $language);
            $revision_article_detail->content = Translate($revision_article->content, $language);
            $revision_article_detail->description = Translate($revision_article->description, $language);
            $revision_article_detail->lang = $language;
            $revision_article_detail->revision_article_id = $revision_article->id;
            $revision_article_detail->save();
        }
        Mail::to(env('ADMIN_MAIL'))->send(new ArticleStatus($revision_article, 'pending'));

        return $this->handleResponse($revision_article, 'Revision article updated successfully');
    }

    public function updateDetail(Request $request, RevisionArticle $revision_article)
    {
        if (!$request->user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
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
        $revision_article_detail = $revision_article->revisionArticleDetail()->where('lang', $language)->first();
        $revision_article_detail->title = $request->title;
        $revision_article_detail->content = $request->content;
        $revision_article_detail->description = $request->description;
        $revision_article_detail->save();

        return $this->handleResponse($revision_article_detail, 'Revision article detail updated successfully');
    }
}
