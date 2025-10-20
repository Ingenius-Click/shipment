<?php

namespace Ingenius\Shipment\Actions;

use Illuminate\Pagination\LengthAwarePaginator;
use Ingenius\Shipment\Models\Beneficiary;

class PaginateBeneficiariesAction {

    public function handle(array $filters = [], int $user_id): LengthAwarePaginator {
        $beneficiaries = Beneficiary::where('user_id', $user_id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
           ;

        return table_handler_paginate($filters, $beneficiaries);
    }

}