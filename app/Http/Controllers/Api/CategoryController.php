<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends BaseController
{

    public function index()
    {
        $category = Category::all();
        return $this->handleResponse($category, 'Category data');
    }

    public function store(CategoryRequest $request)
    {
        $categoryData = $request->except('image');
        $image = $request->image;
        if ($image) {
            $imageName = Str::random(10) . '.' . $image->extension();
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = Storage::url($imagePath);

            $categoryData['image'] = $imageName;
            $categoryData['path'] = $imagePath;
            $categoryData['url'] = $imageUrl;
        }
        $category = Category::create($categoryData);
        return $this->handleResponse($category, 'Category created successfully');
    }

    public function show(Category $category)
    {
        $category->Posts;
        return $this->handleResponse($category, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $categoryData = $request->except('image');

        $image = $request->image;
        if ($image) {
            $imageName = Str::random(10) . '.' . $image->extension();
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = Storage::url($imagePath);

            if ($category->image) {
                Storage::delete($category->path);
            }

            $categoryData['image'] = $imageName;
            $categoryData['path'] = $imagePath;
            $categoryData['url'] = $imageUrl;
        }

        $category->update($categoryData);
        return $this->handleResponse($category, 'Category update successfully!');
    }


    public function destroy(Category $category)
    {
        if ($category->image) {
            Storage::delete($category->path);
        }
        $category->delete();
        return $this->handleResponse(Null, 'Category delete successfully!');
    }
}
