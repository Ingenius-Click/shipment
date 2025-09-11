<?php

namespace Ingenius\Shipment\InvoiceData;

use Ingenius\Orders\Data\InvoiceDataSection;
use Ingenius\Orders\Interfaces\InvoiceDataProviderInterface;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Shipment\Models\Shipment;
use Ingenius\Shipment\Services\ShippingMethodsManager;
use Ingenius\Coins\Services\CurrencyServices;

class ShipmentInvoiceDataProvider implements InvoiceDataProviderInterface
{
    /**
     * @var ShippingMethodsManager
     */
    protected ShippingMethodsManager $shippingMethodsManager;

    /**
     * Create a new shipment invoice data provider.
     *
     * @param ShippingMethodsManager $shippingMethodsManager
     */
    public function __construct(ShippingMethodsManager $shippingMethodsManager)
    {
        $this->shippingMethodsManager = $shippingMethodsManager;
    }

    /**
     * Get the invoice data sections.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getInvoiceData(Invoice $invoice): array
    {
        $orderable_id = $invoice->orderable_id;
        $orderable_type = $invoice->orderable_type;

        if (!$orderable_id || !$orderable_type) {
            return [];
        }

        // Find the shipment for this orderable
        $shipment = Shipment::where('shippable_id', $orderable_id)
            ->where('shippable_type', $orderable_type)
            ->first();

        if (!$shipment) {
            return [];
        }

        $sections = [];

        // Create shipping information section
        $shippingProperties = [];

        // Get shipping method name
        try {
            $shippingMethod = $this->shippingMethodsManager->getShippingMethod(
                $shipment->shipping_method_id,
                true // ignore if not configured
            );
            $shippingProperties[__('Shipping Method')] = __($shippingMethod->getName());
        } catch (\Exception $e) {
            // If shipping method is not found, use the ID
            $shippingProperties[__('Shipping Method')] = __($shipment->shipping_method_id);
        }

        // Add tracking number if available
        if ($shipment->tracking_number) {
            $shippingProperties[__('Tracking Number')] = $shipment->tracking_number;
        }

        // Add shipping cost
        $shippingProperties[__('Shipping Cost')] = $shippingCost ?? '-';

        $sections[] = new InvoiceDataSection(__('Shipping Information'), $shippingProperties, 25);

        // Create delivery information section
        $deliveryProperties = [];

        $deliveryProperties[__('Beneficiary Name')] = $shipment->beneficiary_name;
        $deliveryProperties[__('Beneficiary Email')] = $shipment->beneficiary_email;

        if ($shipment->beneficiary_phone) {
            $deliveryProperties[__('Beneficiary Phone')] = $shipment->beneficiary_phone;
        }

        // Build delivery address
        $addressParts = array_filter([
            $shipment->beneficiary_address,
            $shipment->beneficiary_city,
            $shipment->beneficiary_state,
            $shipment->beneficiary_zip,
            $shipment->beneficiary_country,
        ]);

        if (!empty($addressParts)) {
            $deliveryProperties[__('Delivery Address')] = implode(', ', $addressParts);
        }

        // Add pickup address if available
        if ($shipment->pickup_address) {
            $deliveryProperties[__('Pickup Address')] = $shipment->pickup_address;
        }

        $sections[] = new InvoiceDataSection(__('Delivery Information'), $deliveryProperties, 26);

        return $sections;
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'ShipmentInvoiceDataProvider';
    }

    /**
     * Get the provider priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 25;
    }
}
