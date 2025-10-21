<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ];
    }
}
