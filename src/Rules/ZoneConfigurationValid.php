<?php

namespace Ingenius\Shipment\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Ingenius\Shipment\Enums\ZoneType;
use Ingenius\Shipment\Models\Zone;

class ZoneConfigurationValid implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // Handle validation for province or municipality name strings
        if ($attribute === 'province') {
            $this->validateProvince($value, $fail);
        } elseif ($attribute === 'municipality') {
            $this->validateMunicipality($value, $fail);
        }
    }

    protected function validateProvince(string $provinceName, \Closure $fail): void
    {
        // Find the province by name
        $province = Zone::query()
                    ->where('type', ZoneType::PROVINCE)
                    ->where('name', $provinceName)
                    ->first();

        // if (!$province) {
        //     $fail(__('The selected province is invalid.'));
        //     return;
        // }

        // Check if municipality is also provided in the request
        $municipalityName = $this->data['municipality'] ?? null;

        if (!$province && $municipalityName) {
            // If municipality is provided, check that it belongs to this province
            $municipality = Zone::query()
                ->where('type', ZoneType::MUNICIPALITY)
                ->where('active', true)
                ->where('name', $municipalityName)
                ->where('parent_id', $province->id)
                ->first();

            if (!$municipality) {
                $fail(__('The selected municipality does not belong to the selected province.'));
            }
        }
    }

    protected function validateMunicipality(string $municipalityName, \Closure $fail): void
    {
        // Find the municipality by name
        $municipality = Zone::query()
                            ->where('type', ZoneType::MUNICIPALITY)
                            ->where('name', $municipalityName)
                            ->where('active', true)
                            ->first();

        if (!$municipality) {
            $fail(__('The selected municipality is invalid.'));
            return;
        }

        // Check if the province is also provided and matches
        $provinceName = $this->data['province'] ?? null;

        if ($provinceName && $municipality->parent) {
            if ($municipality->parent->name !== $provinceName) {
                $fail(__('The selected municipality does not belong to the selected province.'));
            }
        }
    }
}
