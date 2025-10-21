<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|required|string|max:500',
            'municipality' => 'sometimes|required|string|max:255',
            'province' => 'sometimes|required|string|max:255',
            'is_default' => 'nullable|boolean',
        ];
    }
}
