<?php

namespace ReninsApi\Request\Soap;

use ReninsApi\Request\Container;

/**
 * Cover
 *
 * @property string $code
 * @property string $sum
 */
abstract class Cover extends Container
{
    protected static $rules = [
        'code' => ['toString', 'required', 'in:UGON,USHERB,DO,NS,DAGO'],
        'sum' => ['toDouble', 'required', 'notEmpty', 'min:0'],
    ];

    protected $code;
    protected $sum;

    public function toXml(\SimpleXMLElement $xml)
    {
        $this->validateThrow();

        $xml->addAttribute('code', $this->code);
        $xml[0] = $this->sum;

        return $this;
    }
}