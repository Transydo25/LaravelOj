<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class CategoryController extends BaseController
{

    public function index()
    {
        $categories = Category::where('status', 'active')->get();
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
        $publishedPosts = $category->posts()->where('status', 'published')->pluck('title')->toArray();
        $category->setAttribute('posts', $publishedPosts);
        return $this->handleResponse($category, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $slug = Str::slug($request->name);
        $user_id = Auth::id();
        $image = $request->image;

        if ($user_id !== $category->author) {
            return $this->handleResponseError([], 'You are not authorized to update this post');
        }
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
        $user_id = Auth::id();

        if ($user_id !== $category->author) {
            return $this->handleError([], 'You are not authorized to deleted this post');
        }
        if ($category->url) {
            $path = 'public' . Str::after($category->url, 'storage');
            Storage::delete($path);
        }
        $category->forceDelete();

        return $this->handleResponse([], 'Category delete successfully!');
    }
}
