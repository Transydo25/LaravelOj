<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CategoryRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'string',
            'type' => 'string',
            'status' => 'in:active,deactive',
            'image' => 'image|mimes:jpg,png,svg|max:10240',
        ];
    }

    public function messages()
    {
        return [
            'name.*' => 'A name is required, must be a string and not greater than 255 character.',
            'status.*' => 'Status must be either "active" or "inactive".',
            'image.*' => 'Image must be an image file file type: jpg, png, svg, and not greater than 10Mb.',
        ];
    }
}
