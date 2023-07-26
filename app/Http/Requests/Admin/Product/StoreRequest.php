<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required'],
            'price' => ['required'],
            'status' => ['required'],
            'categories' => ['nullable']
        ];
    }
    public function messages()
    {
        return [];
    }
}
