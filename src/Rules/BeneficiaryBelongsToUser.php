<?php

namespace Ingenius\Shipment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Shipment\Models\Beneficiary;

class BeneficiaryBelongsToUser implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = AuthHelper::getUser();

        if (!$user) {
            $fail('You must be authenticated to use a beneficiary.');
            return;
        }

        $beneficiary = Beneficiary::find($value);

        if (!$beneficiary) {
            return; // Let the 'exists' rule handle this
        }

        if ($beneficiary->user_id !== $user->id) {
            $fail('The selected beneficiary does not belong to you.');
        }
    }
}
