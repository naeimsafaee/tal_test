<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id'  => 'required|exists:users,id',
            'type'     => 'required|in:buy,sell',
            'quantity' => 'required|numeric|min:0.001',
            'price'    => 'required|integer|min:1',
        ];
    }
}
