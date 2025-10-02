<?php

namespace Ingenius\Shipment\Actions;

use Illuminate\Support\Facades\DB;
use Ingenius\Shipment\Models\Zone;

class BulkActivateZonesAction {
    public function handle(array $data): void {

        DB::transaction(function () use ($data)  {

            foreach ($data['zones'] as $value) {

                $payload = [
                    'active' => $value['active'],
                ];

                Zone::find($value['id'])->update($payload);
            }
        });


    }
}