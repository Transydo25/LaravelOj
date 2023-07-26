<?php

use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

function uploadImage($file, $folder)
{
    $uploaded_image = Image::make($file);
    $input_width = $uploaded_image->getWidth();
    $input_height = $uploaded_image->getHeight();
    $resize_pattern = [
        '720x2000', '1280x2000', '480x2000', '330x2000', '200x2000', '100x2000', '300x300',
    ];
    $size = null;
    $minDistance = PHP_INT_MAX;
    $image_name = Str::random(10);
    if ($folder == 'users') {
        $path = "public/{$folder}/" . date('Y/m/d');
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0777, true, true);
        }
        $image_path = $path . '/' . $image_name;
        Image::make($uploaded_image)->resize(300, 300)->save(storage_path('app/' . $path . '/' . $image_name));

        return asset(Storage::url($image_path));
    }

    foreach ($resize_pattern as $pattern) {
        list($width, $height) = explode('x', $pattern);
        $distance = sqrt(pow($input_width - $width, 2) + pow($input_height - $height, 2));

        if ($distance < $minDistance) {
            $minDistance = $distance;
            $size = $pattern;
        }
    }

    list($width, $height) = explode('x', $size);

    $path = "public/{$folder}/" . date('Y/m/d');
    if (!Storage::exists($path)) {
        Storage::makeDirectory($path, 0777, true, true);
    }
    $image_path = $path . '/' . $image_name;
    Image::make($uploaded_image)->resize($width, $height)->save(storage_path('app/' . $path . '/' . $image_name));

    return asset(Storage::url($image_path));
}
