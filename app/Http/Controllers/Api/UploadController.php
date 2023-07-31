<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;
use Illuminate\Support\Facades\Auth;

class UploadController extends BaseController
{
    private function resize($file, $type)
    {
        $uploadedImage = Image::make($file);
        $inputWidth = $uploadedImage->getWidth();
        $inputHeight = $uploadedImage->getHeight();
        $resizePattern = [
            '720x2000', '1280x2000', '480x2000', '330x2000', '200x2000', '100x2000', '300x300',
        ];
        $size = null;
        $minDistance = PHP_INT_MAX;

        if ($type == 'avatar') {
            $resizedImage = $uploadedImage->resize(300, 300);

            return [
                'width' => 300,
                'image' => $resizedImage
            ];
        }

        if ($type == 'cover') {
            $resizedImage = $uploadedImage->resize(1400, 500);

            return [
                'width' => 1400,
                'image' => $resizedImage
            ];
        }

        foreach ($resizePattern as $pattern) {
            list($width, $height) = explode('x', $pattern);
            $distance = sqrt(pow($inputWidth - $width, 2) + pow($inputHeight - $height, 2));

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $size = $pattern;
            }
        }

        list($width, $height) = explode('x', $size);
        $resizedImage = $uploadedImage->resize($width, $height);

        return [
            'width' => $resizedImage->getWidth(),
            'image' => $resizedImage
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'images' => 'array',
            'images.*' => 'image|mimes:jpg,png,svg|max:10240',
            'type' => 'required|in:content, avatar, cover',
        ]);

        $images = $request->images;
        $path = 'public/upload'  . '/' . date('Y/m/d');
        $upload_data = [];
        $user_id = Auth::id();
        $type = $request->type;

        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0777, true, true);
        }
        foreach ($images as $image) {
            $image_name = Str::random(10);
            $new_image = $this->resize($image, $type);
            $resizedImage = $new_image['image'];
            $width = $new_image['width'];
            $image_path = $path . '/' . $image_name;
            $resizedImage->save(storage_path('app/' . $path . '/' . $image_name));
            $upload = new Upload;
            $upload->url = asset(Storage::url($image_path));
            $upload->path = $image_path;
            $upload->width = $width;
            $upload->status = 'pending';
            $upload->author = $user_id;
            $upload->type = $type;
            $upload->save();
            $upload_data[] = $upload;
        }

        return $this->handleResponse($upload_data, 'Upload created successfully');
    }

    public function uploadVideo(Request $request)
    {
        $request->validate([
            'videos' => 'required|array',
            'videos.*' => 'file|mimes:mp4,avi,mov',
        ]);

        $videos = $request->videos;
        $path = 'public/video'  . '/' . date('Y/m/d');
        $upload_data = [];
        $user_id = Auth::id();

        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0777, true, true);
        }
        foreach ($videos as $video) {
            $video_name = Str::random(10);
            $video_path = $path . '/' . $video_name;
            // $video->save(storage_path('app/' . $path . '/' . $video_name));
            $video->storeAs($path, $video_name . '.' . $video->Extension());
            $upload = new Upload;
            $upload->url = asset(Storage::url($video_path));
            $upload->path = $video_path;
            $upload->status = 'pending';
            $upload->author = $user_id;
            $upload->type = 'video';
            $upload->save();
            $upload_data[] = $upload;
        }

        return $this->handleResponse($upload_data, 'Upload created successfully');
    }
}
