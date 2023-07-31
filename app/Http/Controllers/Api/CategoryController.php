<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;



class CategoryController extends BaseController
{

    public function index(Request $request)
    {
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

        $query = Category::select('*');

        if ($status) {
            $query = $query->where('status', $status);
        }
        if ($search) {
            $query = $query->where('name', 'LIKE', '%' . $search . '%');
        }
        $categories = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($categories, 'Categories data');
    }

    public function store(CategoryRequest $request, Category $category)
    {
        if (!Auth::user()->hasPermission('create')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $slug = Str::slug($request->name);
        $user_id = Auth::id();

        if ($request->upload_ids) {
            $category->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug = $slug;
        $category->type = $request->type;
        $category->status = $request->status;
        $category->author = $user_id;
        $category->save();

        return $this->handleResponse($category, 'Category created successfully');
    }

    public function show(Category $category)
    {
        $category->posts = $category->posts()->where('status', 'published')->pluck('title');
        $upload_ids = json_decode($category->upload_id, true);
        if ($upload_ids) {
            $category->uploads = Upload::whereIn('id', $upload_ids)->get();
        }

        return $this->handleResponse($category, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        if (!Auth::user()->hasPermission('update')) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(403);
        }

        $slug = Str::slug($request->name);

        if ($request->upload_ids) {
            $category->upload_id = json_encode($request->upload_ids);
            handleUploads($request->upload_ids);
        }
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug = $slug;
        $category->type = $request->type;
        $category->status = $request->status;
        $category->save();

        return $this->handleResponse($category, 'Category update successfully!');
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
        Category::onlyTrashed()->whereIn('id', $ids)->restore();

        foreach ($ids as $id) {
            $category = Category::find($id);
            $category->status = 'active';
            $category->save();
        }

        return $this->handleResponse([], 'Category restored successfully!');
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
        $categories = Category::withTrashed()->whereIn('id', $ids)->get();

        foreach ($categories as $category) {
            if ($type === 'force_delete') {
                $upload_ids = json_decode($category->upload_id, true);
                if ($upload_ids) {
                    $uploads = Upload::whereIn('id', $upload_ids)->get();
                }
                foreach ($uploads as $upload) {
                    Storage::delete($upload->path);
                    $upload->delete();
                }
                $category->forceDelete();
            } else {
                $category->status = 'inactive';
                $category->save();
                $category->delete();
            }
        }
        if ($type === 'force_delete') {
            return $this->handleResponse([], 'Category force delete successfully!');
        } else {
            return $this->handleResponse([], 'Category delete successfully!');
        }
    }
}
