<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


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
        $limit = request()->input('limit') ?? 10;

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
        $slug = Str::slug($request->name);
        $user_id = Auth::id();
        $image = $request->image;

        if ($image) {
            $imageName = Str::random(10);
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = asset(Storage::url($imagePath));
            $category->url = $imageUrl;
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
        return $this->handleResponse($category, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $slug = Str::slug($request->name);
        $image = $request->image;

        if ($image) {
            if ($category->url) {
                $path = 'public' . Str::after($category->url, 'storage');
                Storage::delete($path);
            }
            $imageName = Str::random(10);
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = asset(Storage::url($imagePath));
            $category->url = $imageUrl;
        }
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug = $slug;
        $category->type = $request->type;
        $category->status = $request->status;

        $category->save();
        return $this->handleResponse($category, 'Category update successfully!');
    }


    public function destroy(Category $category)
    {
        if ($category->url) {
            $path = 'public' . Str::after($category->url, 'storage');
            Storage::delete($path);
        }
        $category->delete();

        return $this->handleResponse([], 'Category delete successfully!');
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->find($id);
        $category->restore();

        return $this->handleResponse([], 'Category restored successfully!');
    }

    public function forceDelete($id)
    {
        $category = Category::withTrashed()->find($id);

        if ($category->trashed()) {
            $category->forceDelete();
            return $this->handleResponse([], 'Category force deleted successfully!');
        } else {
            return $this->handleResponse([], 'Category is not in trash. Cannot force delete!');
        }
    }
}
