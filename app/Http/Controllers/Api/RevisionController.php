<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Article;
use App\Models\Revision;
use App\Models\RevisionDetail;
use App\Models\Upload;


class RevisionController extends BaseController
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
        $status = in_array($status, $layout_status) ? $status : 'published';
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $language = $request->language;
        $language = in_array($language, $languages) ? $language : '';
        $article_id = $request->article_id;
        $query = Revision::select('*')
            ->groupBy('article_id')
            ->latest('version');
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
            $query = $query->whereHas('articleDetail', function ($q) use ($language) {
                $q->where('language', $language);
            });
            $query = $query->with(['articleDetail' => function ($q) use ($language) {
                $q->where('language', $language);
            }]);
        }
        $revisions = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($revisions, 'users data');
    }

    public function store(Request $request, Article $article)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $revision = new Revision;
        $languages = config('app.languages');

        $revision->article_id = $article->id;
        $revision->title = $article->title;
        $revision->description = $article->description;
        $revision->content = $article->content;
        $revision->upload_id = $article->upload_id;
        $revision->version = $article->revision()->where('article_id', $article->id)->count() + 1;
        $revision->user_id = $article->user_id;
        $revision->status = 'pending';
        $revision->save();
        foreach ($languages as $language) {
            $revision_detail = new RevisionDetail;
            $revision_detail->title = Translate($revision->title, $language);
            $revision_detail->content = Translate($revision->content, $language);
            $revision_detail->description = Translate($revision->description, $language);
            $revision_detail->revision_id = $revision->id;
            $revision_detail->lang = $language;
            $revision_detail->save();
        }

        return $this->handleResponse($revision, 'revision created successfully');
    }


    public function show(Request $request, Revision $revision)
    {
        $language = $request->language;
        if ($language) {
            $revision->revision_detail = $revision->revisionDetail()->where('lang', $language)->get();
        }
        $revision->categories = $revision->category()->where('status', 'active')->pluck('name');
        $upload_ids = json_decode($revision->upload_id, true);
        if ($upload_ids) {
            $revision->uploads = Upload::whereIn('id', $upload_ids)->get();
        }
        return $this->handleResponse($revision, 'article data details');
    }


    public function update(Request $request, Revision $revision)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'title' => 'required|string|max: 255',
            'content' => 'string',
            'description' => 'string',
        ]);

        $languages = config('app.languages');

        if ($request->upload_ids) {
            $revision->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $revision->title = $request->title;
        $revision->description = $request->description;
        $revision->content = $request->content;
        $revision->save();
        foreach ($languages as $language) {
            $revision_detail = new RevisionDetail;
            $revision_detail->title = Translate($revision->title, $language);
            $revision_detail->content = Translate($revision->content, $language);
            $revision_detail->description = Translate($revision->description, $language);
            $revision_detail->lang = $language;
            $revision_detail->save();
        }

        return $this->handleResponse($revision, 'revision updated successfully');
    }

    public function updateDetail(Request $request, Revision $revision)
    {
        if (!Auth::user()->hasPermission('update')) {
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
        $revision_detail = $revision->revisionDetail()->where('lang', $language)->first();
        $revision_detail->title = $request->title;
        $revision_detail->content = $request->content;
        $revision_detail->description = $request->description;
        $revision_detail->save();

        return $this->handleResponse($revision_detail, 'revision detail updated successfully');
    }

    public function destroy($id)
    {
        //
    }
}
