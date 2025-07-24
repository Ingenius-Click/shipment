<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shippingMethodTable = config('shipment.shipping_methods_table', 'shipping_methods');

        return [
            'shipping_method_id' => 'required|string|exists:' . $shippingMethodTable . ',shipping_method_id',
        ];
    }
}
