<?php

namespace Ingenius\Shipment\Policies;

use Ingenius\Shipment\Constants\ZonePermissions;

class ZonePolicy
{
    public function viewAny($user)
    {
        $userClass = tenant_user_class();

        if( $user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(ZonePermissions::INDEX);
        }

        return false;
    }

    public function activate($user)
    {
        $userClass = tenant_user_class();

        if( $user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(ZonePermissions::ACTIVATE);
        }

        return false;
    }
}