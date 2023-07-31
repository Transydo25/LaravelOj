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

    public function index(Request $request)
    {
        $language = $request->input('language');
        $languages = config('app.languages');
        $language = in_array($language, $languages) ? $language : '';
        $status = $request->input('status');
        $layout_status = ['active', 'inactive'];
        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['name', 'created_at', 'updated_at'];
        $sort_by = $request->input('sort_by');
        $status = in_array($status, $layout_status) ? $status : 'active';
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $query = TopPage::select('*');

        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        if ($language) {
            $query = $query->whereHas('TopPageDetail', function ($q) use ($language) {
                $q->where('lang', $language);
            });
            $query = $query->with(['TopPageDetail' => function ($q) use ($language) {
                $q->where('lang', $language);
            }]);
        }
        $top_pages = $query->orderBy($sort_by, $sort)->paginate($limit);
        foreach ($top_pages as $top_page) {
            $upload_ids = json_decode($top_page->upload_id, true);
            if ($upload_ids) {
                $top_page->uploads = Upload::whereIn('id', $upload_ids)->get();
            }
        }

        return $this->handleResponse($top_pages, 'TopPage data');
    }


    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        // dd(Auth::user()->topPage);
        if (!Auth::user()->topPage) {
            return $this->handleResponse([], 'you already have the page, can not create more');
        }

        $request->validate([
            'name' => 'required|string|max: 255',
            'area' => 'required|string|max: 255',
            'summary' => 'required|string|max: 200',
            'about' => 'required|string|max: 1000',
            'official_website' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
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
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'name' => 'required|string|max: 255',
            'area' => 'required|string|max: 255',
            'summary' => 'required|string|max: 200',
            'about' => 'required|string|max: 1000',
            'official_website' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'status' => 'in:active,inactive',
        ]);

        $languages = config('app.languages');

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
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $request->validate([
            'name' => 'required|string|max: 255',
            'area' => 'required|string|max: 255',
            'summary' => 'required|string|max: 200',
            'about' => 'required|string|max: 1000',
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
