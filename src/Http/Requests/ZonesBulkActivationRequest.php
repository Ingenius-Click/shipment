<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZonesBulkActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zones' => 'required|array',
            'zones.*.id' => 'required|numeric|exists:zones,id',
            'zones.*.active' => 'required|boolean',
        ];
    }
}