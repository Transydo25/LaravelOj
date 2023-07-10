<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;

class CategoryController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $category = Category::all();
        return $this->handleResponse($category, 'Category data');
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->all());
        return $this->handleResponse($category, 'Category created successfully');
    }

    public function show(Category $category)
    {
        $category->Posts;
        return $this->handleResponse($category, 'Category data');
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->all());
        return $this->handleResponse($category, 'Category update successfully!');
    }


    public function destroy(Category $category)
    {
        $category->delete();
        return $this->handleResponse(Null, 'Category delete successfully!');
    }
}
