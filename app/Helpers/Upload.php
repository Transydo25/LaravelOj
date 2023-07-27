<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Upload;


function handleUploads($upload_ids, $targetId, $target)
{
    $user_id = Auth::id();
    foreach ($upload_ids as $upload_id) {
        $upload = Upload::find($upload_id);
        if ($upload && $upload->author === $user_id && $upload->status === 'pending') {
            $upload->$target = $targetId;
            $upload->status = 'active';
            $upload->save();
        }
    }
    $uploads = Upload::where('author', $user_id)
        ->where('status', 'pending')
        ->whereNull($target)
        ->get();
    foreach ($uploads as $upload) {
        Storage::delete($upload->path);
        $upload->delete();
    }
}
