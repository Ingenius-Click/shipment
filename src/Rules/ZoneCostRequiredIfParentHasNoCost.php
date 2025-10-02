<?php

namespace Ingenius\Shipment\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Ingenius\Shipment\Models\Zone;

class ZoneCostRequiredIfParentHasNoCost implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // Extract the zone index from the attribute (e.g., "zones.0.cost" -> 0)
        preg_match('/zones\.(\d+)\.cost/', $attribute, $matches);
        $index = $matches[1] ?? null;

        if ($index === null) {
            return;
        }

        $zoneId = $this->data['zones'][$index]['id'] ?? null;
        $zone = Zone::find($zoneId);

        if (!$zone) {
            return;
        }

        // If cost is null and zone has no parent, it must have a value
        if ($value === null && !$zone->parent_id) {
            $fail(__('Cost is required for zones without a parent.'));
            return;
        }

        // If cost is null, check if parent has a value
        if ($value === null && $zone->parent_id) {
            $parentZoneConfig = collect($this->data['zones'] ?? [])
                ->firstWhere('id', $zone->parent_id);

            if (!$parentZoneConfig || $parentZoneConfig['cost'] === null) {
                $fail(__('Cost is required when the parent zone does not have a cost value.'));
            }
        }
    }
}
