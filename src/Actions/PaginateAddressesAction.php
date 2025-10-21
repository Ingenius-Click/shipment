<?php

namespace Ingenius\Shipment\Actions;

use Illuminate\Pagination\LengthAwarePaginator;
use Ingenius\Shipment\Models\Address;

class PaginateAddressesAction {

    public function handle(array $filters = [], int $user_id): LengthAwarePaginator {
        $addresses = Address::where('user_id', $user_id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
           ;

        return table_handler_paginate($filters, $addresses);
    }

}
