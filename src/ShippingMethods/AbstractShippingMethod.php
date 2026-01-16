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
                'active' => true
            ]);
        }

        $this->name = $shippingMethodData->name;
        $this->calculationData = $shippingMethodData->calculation_data;
        $this->active = $shippingMethodData->active;
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
        if (!$this->isDataValid($data)) {
            throw new ValidationException(Validator::make($data, $this->configDataRules()));
        }

        $shippingMethodData = ShippingMethodData::where('shipping_method_id', $this->id)->firstOrFail();

        $shippingMethodData->calculation_data = $data;

        $shippingMethodData->save();

        $this->calculationData = $data;
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
