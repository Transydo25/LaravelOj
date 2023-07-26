<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;

class UploadController extends BaseController
{
    private function resize($file)
    {
        $uploadedImage = Image::make($file);
        $inputWidth = $uploadedImage->getWidth();
        $inputHeight = $uploadedImage->getHeight();
        $resizePattern = [
            '720x2000', '1280x2000', '480x2000', '330x2000', '200x2000', '100x2000', '300x300',
        ];
        $size = null;
        $minDistance = PHP_INT_MAX;

        foreach ($resizePattern as $pattern) {
            list($width, $height) = explode('x', $pattern);
            $distance = sqrt(pow($inputWidth - $width, 2) + pow($inputHeight - $height, 2));

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $size = $pattern;
            }
        }

        list($width, $height) = explode('x', $size);
        return $uploadedImage->resize($width, $height);
    }

    public function store(Request $request)
    {
        $request->validate([
            'images' => 'array',
            'images.*' => 'image|mimes:jpg,png,svg|max:10240',
        ]);

        $images = $request->images;
        $folder = $request->folder;
        $path = 'public/' . $folder . '/' . date('Y/m/d');
        $upload_data = [];

        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0777, true, true);
        }
        foreach ($images as $image) {
            $image_name = Str::random(10);
            $resizedImage = $this->resize($image);
            $image_path = $path . '/' . $image_name;
            $resizedImage->save(storage_path('app/' . $path . '/' . $image_name));
            $upload = new Upload;
            $upload->url = asset(Storage::url($image_path));
            $upload->path = $image_path;
            $upload->save();
            $upload_data[] = $upload;
        }

        return $this->handleResponse($upload_data, 'Upload created successfully');
    }

    public function update(Request $request)
    {
        $images = $request->images;
        $type_type = $request->type_type;
        $type_id = $request->type_id;
        $uploads = Upload::where('type_type', $type_type)
            ->where('type_id', $type_id)
            ->get();
        $folder = $request->folder;
        $path = 'public/' . $folder . '/' . date('Y/m/d');
        $upload_data = [];
        if ($uploads) {
            foreach ($uploads as $upload) {
                Storage::delete($upload->path);
                $upload->delete();
            }
        }

        foreach ($images as $image) {
            $image_name = Str::random(10);
            $resizedImage = $this->resize($image);
            $image_path = $path . '/' . $image_name;
            $resizedImage->save(storage_path('app/' . $path . '/' . $image_name));
            $upload = new Upload;
            $upload->url = asset(Storage::url($image_path));
            $upload->path = $image_path;
            $upload->type_type = $type_type;
            $upload->type_id = $type_id;
            $upload->save();
            $upload_data[] = $upload;
        }
        return $this->handleResponse($uploads, 'Uploads updated successfully');
    }
}
