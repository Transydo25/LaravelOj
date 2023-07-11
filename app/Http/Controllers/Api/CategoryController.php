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
        $categories = Category::all();
        return $this->handleResponse($categories, 'Categories data');
    }

    public function store(CategoryRequest $request)
    {
        $slug = Str::slug($request->name);
        $user_id = Auth::id();
        $image = $request->image;
        $category = new Category();

        $category->fill($request->all());
        $category->slug = $slug;
        $category->author = $user_id;
        if ($image) {
            $imageName = Str::random(10);
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = asset(Storage::url($imagePath));

            $category->url = $imageUrl;
        }

        $category->save();
        return $this->handleResponse($category, 'Category created successfully');
    }

    public function show(Category $category)
    {
        $data = $category;
        return $this->handleResponse($data, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $slug = Str::slug($request->name);
        $user_id = Auth::id();
        $image = $request->image;

        $category->fill($request->all());
        $category->slug = $slug;
        $category->author = $user_id;
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
}
