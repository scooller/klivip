<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', 'in:web,mobile,qr_scan,api,qr,customer-form'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.in' => 'El canal no es válido',
        ];
    }
}
