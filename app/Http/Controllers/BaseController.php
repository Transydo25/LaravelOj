<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function handleResponse($data, $message)
    {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message
        ];

        return response()->json($response);
    }
    public function handleResponseError($data, $message)
    {
        $response = [
            'success' => false,
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response);
    }
}
