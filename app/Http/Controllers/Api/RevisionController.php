<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Article;
use App\Models\Revision;
use App\Models\RevisionDetail;


class RevisionController extends Controller
{

    public function index()
    {
        $revisions = Revision::select('*')
            ->groupBy('article_id')
            ->latest('version')
            ->get();

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
        $revision->version = $article->revisions()->where('article_id', $article->id)->count() + 1;
        $revision->user_id = $article->user_id;
        $revision->status = 'pending';
        $revision->save();
        foreach ($languages as $language) {
            $revision_detail = new RevisionDetail;
            $revision_detail->title = Translate($revision->title, $language);
            $revision_detail->content = Translate($revision->content, $language);
            $revision_detail->description = Translate($revision->description, $language);
            $revision_detail->lang = $language;
            $revision_detail->save();
        }

        return $this->handleResponse($revision, 'revision created successfully');
    }


    public function show($id)
    {
        //
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
