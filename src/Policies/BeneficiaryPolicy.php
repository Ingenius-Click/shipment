<?php

namespace Ingenius\Shipment\Policies;

use Ingenius\Shipment\Models\Beneficiary;

class BeneficiaryPolicy
{
    /**
     * Determine if the user can view any beneficiaries.
     */
    public function viewAny($user): bool
    {
        $userClass = tenant_user_class();

        return $user && is_object($user) && is_a($user, $userClass);
    }

    /**
     * Determine if the user can view the beneficiary.
     */
    public function view($user, Beneficiary $beneficiary): bool
    {
        return $user && $user->id === $beneficiary->user_id;
    }

    /**
     * Determine if the user can create beneficiaries.
     */
    public function create($user): bool
    {
        $userClass = tenant_user_class();

        return $user && is_object($user) && is_a($user, $userClass);
    }

    /**
     * Determine if the user can update the beneficiary.
     */
    public function update($user, Beneficiary $beneficiary): bool
    {
        return $user && $user->id === $beneficiary->user_id;
    }

    /**
     * Determine if the user can delete the beneficiary.
     */
    public function delete($user, Beneficiary $beneficiary): bool
    {
        return $user && $user->id === $beneficiary->user_id;
    }
}
