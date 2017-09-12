<?php

namespace ReninsApi\Request\Soap;

use ReninsApi\Request\Container;

/**
 * Car identification
 *
 * @property string $LicensePlate
 * @property string $VIN
 * @property string $BodyNumber
 * @property string $ChassisNumber
 */
class CarIdent extends Container
{
    protected static $rules = [
        'LicensePlate' => 'toString',
        'VIN' => 'toString',
        'BodyNumber' => 'toString',
        'ChassisNumber' => 'toString',
    ];

    public function validate()
    {
        $errors = parent::validate();

        if ($this->LicensePlate == ''
            && $this->VIN == ''
            && $this->BodyNumber == ''
            && $this->ChassisNumber == '') {
            $errors['VIN'][] = "One of field must be specified";
        }

        return $errors;
    }

    protected $LicensePlate;
    protected $VIN;
    protected $BodyNumber;
    protected $ChassisNumber;
}