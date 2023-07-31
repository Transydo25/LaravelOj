<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\TopPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


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
        $TopPage = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($TopPage, 'TopPage data');
    }


    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        if (Auth::user()->topPage) {
            return $this->handleResponse([], 'you already have the page, can not create more');
        }

        $request->validate([
            'name' => 'required|string|max: 255',
            'description' => 'string',
            'content' => 'string',
            'status' => 'in:active,inactive',
        ]);
    }


    public function show($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }
}
