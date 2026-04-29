<?php

namespace Ingenius\Shipment\ShippingMethods;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Ingenius\Core\Interfaces\FeatureInterface;
use Ingenius\Core\Interfaces\HasFeature;
use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Exceptions\NoShippingMethodTableException;
use Ingenius\Shipment\Models\ShippingMethodData;
use Ingenius\Shipment\ShippingMethods\Response\CalculationResponse;
use JsonSerializable;

abstract class AbstractShippingMethod implements Arrayable, Jsonable, JsonSerializable, HasFeature
{
    protected $table;

    protected $id;

    protected $name;

    protected $active = true;

    protected $calculationData = [];

    protected bool $isExternal = false;

    protected ?string $externalPaymentInstructions = null;


    public function __construct()
    {
        $this->table = config('shipment.shipping_methods_table', 'shipping_methods');

        $this->init();
    }

    abstract public function getRequiredFeature(): FeatureInterface;

    abstract public function getType(): ShippingTypes;

    public function getActive(): bool
    {
        return $this->active;
    }

    protected function init(): void
    {
        if (!Schema::hasTable($this->table)) {
            Log::info('Shipping methods table not found');
            return;
        }

        $shippingMethodData = ShippingMethodData::where('shipping_method_id', $this->id)->first();

        if (!$shippingMethodData) {
            $shippingMethodData = ShippingMethodData::create([
                'shipping_method_id' => $this->id,
                'calculation_data' => $this->getDefaultConfigData(),
                'name' => $this->getName() ?? implode(' ', preg_split('/(?=[A-Z])/', class_basename(get_class($this)), -1, PREG_SPLIT_NO_EMPTY)),
                'active' => true,
                'is_external' => false,
                'external_payment_instructions' => null,
            ]);
        }

        $this->name = $shippingMethodData->name;
        $this->calculationData = $shippingMethodData->calculation_data;
        $this->active = $shippingMethodData->active;
        $this->isExternal = $this->canBeExternal() ? (bool) $shippingMethodData->is_external : false;
        $this->externalPaymentInstructions = $shippingMethodData->external_payment_instructions;
    }

    public function canBeExternal(): bool
    {
        return true;
    }

    public function getIsExternal(): bool
    {
        return $this->isExternal;
    }

    public function getExternalPaymentInstructions(): ?string
    {
        return $this->externalPaymentInstructions;
    }

    public function getConfigData(): array
    {
        return $this->calculationData;
    }

    public function renderFormData(): array
    {
        return [];
    }

    protected function getDefaultConfigData(): array
    {
        return [];
    }

    public function setConfigData(array $data): void
    {
        $hasExternalFlag = \array_key_exists('is_external', $data);
        $hasExternalInstructions = \array_key_exists('external_payment_instructions', $data);

        $isExternal = $data['is_external'] ?? null;
        $externalInstructions = $data['external_payment_instructions'] ?? null;
        unset($data['is_external'], $data['external_payment_instructions']);

        if (!$this->isDataValid($data)) {
            throw new ValidationException(Validator::make($data, $this->configDataRules()));
        }

        $shippingMethodData = ShippingMethodData::where('shipping_method_id', $this->id)->firstOrFail();

        $shippingMethodData->calculation_data = $data;

        if ($hasExternalFlag) {
            $shippingMethodData->is_external = $this->canBeExternal() ? (bool) $isExternal : false;
        }

        if ($hasExternalInstructions) {
            $shippingMethodData->external_payment_instructions = $externalInstructions;
        }

        $shippingMethodData->save();

        $this->calculationData = $data;
        $this->isExternal = (bool) $shippingMethodData->is_external;
        $this->externalPaymentInstructions = $shippingMethodData->external_payment_instructions;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function calculate(array $data): CalculationResponse;

    abstract public function rules(): array;

    public function configDataRules(): array
    {
        return [];
    }

    public function configFormDataSchema(): array {
        return [];
    }

    public function configExternalData(): array {
        return [];
    }

    public function configured(): bool
    {
        return $this->isDataValid($this->getConfigData());
    }

    public function isDataValid(array $data): bool
    {
        $validator = Validator::make($data, $this->configDataRules());
        return $validator->passes();
    }

    protected function validateRules(array $data): void
    {
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => __($this->name),
            'type' => $this->getType()->value,
            'configured' => $this->configured(),
            'is_external' => $this->isExternal,
            'external_payment_instructions' => $this->externalPaymentInstructions,
            'can_be_external' => $this->canBeExternal(),
            'calculation_data_rules' => $this->rules(),
            'calculation_data_form' => $this->renderFormData(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
