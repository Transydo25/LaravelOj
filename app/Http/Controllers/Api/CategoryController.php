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

    public function index()
    {
        $category = Category::all();
        return $this->handleResponse($category, 'Category data');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
            'image' => 'image|mimes:jpg,png,svg|max:10240',
        ], [
            'name.*' => 'A name is required, must be a string, not greater than 255 characters',
            'status.*' => 'A status is in: active, inactive',
            'image.*' => 'Image is a file, type of: jpg, svg, png and not greater than 10Mb',
        ]);
        $categoryData = $request->except('image');
        $slug = Str::slug($request->name);
        $categoryData['slug'] = $slug;
        $user = Auth::user();
        $categoryData['author'] = $user->email;
        $image = $request->image;
        if ($image) {
            $imageName = Str::random(10) . '.' . $image->extension();
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = asset(Storage::url($imagePath));

            $categoryData['image'] = $imageName;
            $categoryData['path'] = $imagePath;
            $categoryData['url'] = $imageUrl;
        }
        $category = Category::create($categoryData);
        return $this->handleResponse($category, 'Category created successfully');
    }

    public function show(Category $category)
    {
        $data = $category;
        return $this->handleResponse($data, 'Category data');
    }


    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'string|max:255',
            'status' => 'in:active,inactive',
            'image' => 'image|mimes:jpg,png,svg|max:10240',
        ], [
            'name.*' => 'A name must be a string, not greater than 255 characters',
            'status.*' => 'A status is in: active, inactive',
            'image.*' => 'Image is a file, type of: jpg, svg, png and not greater than 10Mb',
        ]);
        $categoryData = $request->except('image');
        $user = Auth::user();
        $categoryData['author'] = $user->email;
        $image = $request->image;
        if ($image) {
            $imageName = Str::random(10) . '.' . $image->extension();
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = asset(Storage::url($imagePath));

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
        return $this->handleResponse([], 'Category delete successfully!');
    }
}
