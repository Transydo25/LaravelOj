<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\TopPage;
use App\Models\TopPageDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Upload;


class TopPageController extends BaseController
{

    public function store(Request $request)
    {
        if (!$request->user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        if ($request->user()->topPage()->exists()) {
            return $this->handleResponse([], 'You already have a top_page, cannot create more');
        }

        $request->validate([
            'area' => 'required|regex:/^[a-zA-Z0-9]+\/[a-zA-Z0-9]+$/',
            'about' => 'required|string|max:200',
            'summary' => 'required|string|max:1000',
            'name' => 'required',
            'facebook' => 'url|starts_with:https://www.facebook.com/',
            'instagram' => 'url|starts_with:https://www.instagram.com/',
            'official_website' => 'url',
            'status' => 'in:active,inactive',
        ]);

        $top_page = new TopPage;
        $user_id = Auth::id();
        $languages = config('app.languages');

        if ($request->upload_ids) {
            $top_page->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $top_page->user_id = $user_id;
        $top_page->name = $request->name;
        $top_page->area = $request->area;
        $top_page->about = $request->about;
        $top_page->summary = $request->summary;
        $top_page->official_website = $request->official_website;
        $top_page->facebook_link = $request->facebook_link;
        $top_page->instagram_link = $request->instagram_link;
        $top_page->status = $request->status;
        $top_page->save();
        foreach ($languages as $language) {
            $top_page_detail = new TopPageDetail;
            $top_page_detail->name = translate($request->name, $language);
            $top_page_detail->area = translate($request->area, $language);
            $top_page_detail->about = translate($request->about, $language);
            $top_page_detail->summary = translate($request->summary, $language);
            $top_page_detail->top_page_id = $top_page->id;
            $top_page_detail->lang = $language;
            $top_page_detail->save();
        }

        return $this->handleResponse($top_page, 'Top page created successfully');
    }


    public function show(Request $request, TopPage $top_page)
    {
        return $this->handleResponse($top_page, 'top_page data details');

        $language = $request->language;
        if ($language) {
            $top_page->top_page_detail = $top_page->topPageDetail()->where('lang', $language)->get();
        }
        $upload_ids = json_decode($top_page->upload_id, true);
        if ($upload_ids) {
            $top_page->uploads = Upload::whereIn('id', $upload_ids)->get();
        }
        return $this->handleResponse($top_page, 'top_page data details');
    }


    public function update(Request $request, TopPage $top_page)
    {
        if (!$request->user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'area' => 'required|regex:/^[a-zA-Z0-9]+\/[a-zA-Z0-9]+$/',
            'about' => 'required|string|max:200',
            'summary' => 'required|string|max:1000',
            'name' => 'required',
            'facebook' => 'url|starts_with:https://www.facebook.com/',
            'instagram' => 'url|starts_with:https://www.instagram.com/',
            'official_website' => 'url',
            'status' => 'in:active,inactive',
        ]);

        $languages = config('app.languages');

        if ($request->upload_ids) {
            $top_page->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $top_page->name = $request->name;
        $top_page->area = $request->area;
        $top_page->about = $request->about;
        $top_page->summary = $request->summary;
        $top_page->official_website = $request->official_website;
        $top_page->facebook_link = $request->facebook_link;
        $top_page->instagram_link = $request->instagram_link;
        $top_page->status = $request->status;
        $top_page->save();
        $top_page->topPageDetail()->delete();
        foreach ($languages as $language) {
            $top_page_detail = new TopPageDetail;
            $top_page_detail->name = translate($request->name, $language);
            $top_page_detail->area = translate($request->area, $language);
            $top_page_detail->about = translate($request->about, $language);
            $top_page_detail->summary = translate($request->summary, $language);
            $top_page_detail->top_page_id = $top_page->id;
            $top_page_detail->lang = $language;
            $top_page_detail->save();
        }

        return $this->handleResponse($top_page, 'Top page updated successfully');
    }

    public function updateDetails(Request $request, TopPage $top_page)
    {
        if (!$request->user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'area' => 'required|regex:/^[a-zA-Z0-9]+\/[a-zA-Z0-9]+$/',
            'about' => 'required|string|max:200',
            'summary' => 'required|string|max:1000',
            'name' => 'required',
        ]);

        $language = $request->language;

        if (!($language && in_array($language, config('app.languages')))) {
            return $this->handleResponse([], 'Not Found Language');
        }
        $top_page_detail = $top_page->topPageDetail()->where('lang', $language)->first();
        $top_page_detail->name = $request->name;
        $top_page_detail->area = $request->area;
        $top_page_detail->about = $request->about;
        $top_page_detail->summary = $request->summary;
        $top_page_detail->save();

        return $this->handleResponse($top_page_detail, 'Top page detail updated successfully');
    }
}
