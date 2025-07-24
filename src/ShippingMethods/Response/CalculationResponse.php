<?php

namespace Ingenius\Shipment\ShippingMethods\Response;

class CalculationResponse
{
    public function __construct(
        public int $price,
        public string $base_currency_code,
    ) {}
}
