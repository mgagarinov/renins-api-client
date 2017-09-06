<?php

namespace RetailCrm\Request\Rest;

use RetailCrm\Request\Container;

/**
 * Params for REST /Vehicle/Brands/All
 * @property $vehicleType string
 */
class VehicleBrandsAll extends Container
{
    protected $vehicleType;

    public function setVehicleType($value) {
        $this->vehicleType = (string) $value;
        return $this;
    }

    public function getVehicleType() {
        return $this->vehicleType;
    }
}