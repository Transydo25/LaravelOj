<?php

namespace App\Http\Controllers\Api;

use App\Models\Media;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\MediaRequest;
use App\Http\Controllers\BaseController;




class MediaController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    private function generateImageName($extension)
    {
        return Str::random(10) . '.' . $extension;
    }

    public function index()
    {
        $media = Media::all();
        $message = 'Media data';
        return $this->handleResponse($media, $message);
    }


    public function store(MediaRequest $request)
    {
        $image = $request->image;
        $media = new Media();
        if ($image) {
            $imageName = $this->generateImageName($image->extension());
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = Storage::url($imagePath);
            $media->image = $imageName;
            $media->path = $imagePath;
            $media->url = $imageUrl;
            $media->save();
            return $this->handleResponse($media, 'Media create successfully!');
        }
        return $this->handleResponseErros('', 'No image');
    }


    public function show(Media $media)
    {
        return $this->handleResponse($media, 'Media data');;
    }


    public function update(MediaRequest $request, Media $media)
    {
        $image = $request->image;
        if ($image) {
            if ($media->name) {
                Storage::delete($media->path);
            }
            $imageName = $this->generateImageName($image->extension());
            $imagePath = $image->storeAs('public/upload/' . date('Y/m/d'), $imageName);
            $imageUrl = Storage::url($imagePath);
            $media->image = $imageName;
            $media->path = $imagePath;
            $media->url = $imageUrl;
            $media->save();
            return $this->handleResponse($media, 'Media update successfully!');
        }
        return $this->handleResponse($media, 'false');
    }


    public function destroy(Media $media)
    {
        if (Storage::delete($media->path)) {
            $media->delete();
            return $this->handleResponse(Null, 'Media delete successfully!');
        }
        return $this->handleResponse($media, 'false');
    }
}
