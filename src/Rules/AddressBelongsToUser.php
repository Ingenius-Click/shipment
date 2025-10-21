<?php

namespace Ingenius\Shipment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Shipment\Models\Address;

class AddressBelongsToUser implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = AuthHelper::getUser();

        if (!$user) {
            $fail('You must be authenticated to use an address.');
            return;
        }

        $address = Address::find($value);

        if (!$address) {
            return; // Let the 'exists' rule handle this
        }

        if ($address->user_id !== $user->id) {
            $fail('The selected address does not belong to you.');
        }
    }
}
