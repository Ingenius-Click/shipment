<?php

namespace Ingenius\Shipment\Policies;

use Ingenius\Shipment\Models\Address;

class AddressPolicy
{
    /**
     * Determine if the user can view any addresses.
     */
    public function viewAny($user): bool
    {
        $userClass = tenant_user_class();

        return $user && is_object($user) && is_a($user, $userClass);
    }

    /**
     * Determine if the user can view the address.
     */
    public function view($user, Address $address): bool
    {
        return $user && $user->id === $address->user_id;
    }

    /**
     * Determine if the user can create addresses.
     */
    public function create($user): bool
    {
        $userClass = tenant_user_class();

        return $user && is_object($user) && is_a($user, $userClass);
    }

    /**
     * Determine if the user can update the address.
     */
    public function update($user, Address $address): bool
    {
        return $user && $user->id === $address->user_id;
    }

    /**
     * Determine if the user can delete the address.
     */
    public function delete($user, Address $address): bool
    {
        return $user && $user->id === $address->user_id;
    }
}
