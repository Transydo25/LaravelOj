<?php

use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

function upload($file, $folder)
{
    $uploaded_image = Image::make($file);
    $input_width = $uploaded_image->getWidth();
    $input_height = $uploaded_image->getHeight();
    $resize_pattern = config('app.resize_patterns');
    $size = null;
    $minDistance = PHP_INT_MAX;
    $image_name = Str::random(10);


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
