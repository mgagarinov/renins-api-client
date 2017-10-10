<?php

namespace ReninsApi\Request;

class Validator
{
    protected $rules;
    protected $errors;

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function validate(array $data)
    {
        $this->errors = [];

        foreach ($this->rules as $property => $propRules) {
            $value = (array_key_exists($property, $data)) ? $data[$property] : null;

            if (!is_array($propRules)) {
                $propRules = explode(',', $propRules);
            }
            foreach ($propRules as $propRule) {
                $propRule = trim($propRule);
                if ($propRule == '' || substr($propRule, 0, 2) == 'to') continue;

                $params = null;
                $pos = mb_strpos($propRule, ':');
                if ($pos !== false) {
                    $params = mb_substr($propRule, $pos + 1);
                    $propRule = mb_substr($propRule, 0, $pos);
                }

                $method = 'check' . ucfirst($propRule);
                if (!method_exists($this, $method)) {
                    throw new ValidatorException("Rule {$propRule} isn't supported");
                }

                $res = $this::{$method}($value, $params);
                if ($res !== true) {
                    $this->errors[$property][] = $res;
                }
            }
        }

        return count($this->errors) == 0;
    }

    /*
     * VALIDATION METHODS
     */

    public static function checkRequired($value, $params = null)
    {
        if ($value === null) {
            return 'Is required';
        }
        return true;
    }

    public static function checkNotEmpty($value, $params = null)
    {
        if ($value === null) return true;

        if (is_string($value)) {
            //"0" isn't empty
            if ($value === '') {
                return 'Is empty';
            }
        } elseif ($value instanceof ContainerCollection) {
            if ($value->count() <= 0) {
                return 'Is empty';
            }
        } elseif (empty($value)) {
            return 'Is empty';
        }
        return true;
    }

    public static function checkLogical($value, $params = null)
    {
        return static::checkIn($value, 'YES|NO');
    }

    public static function checkIn($value, $params = null)
    {
        if ($value === null) return true;

        $paramsArr = explode('|', $params);
        $paramsArr = array_map(function ($v) {
            return trim($v);
        }, $paramsArr);
        if (!in_array((string)$value, $paramsArr, true)) {
            $need = '';
            $cutted = false;
            foreach ($paramsArr as $index => $param) {
                $need .= (($need != '') ? ', ' : '') . $param;
                if (mb_strlen($need) > 50) {
                    if ($index < count($paramsArr) - 1) {
                        //есть еще значения
                        $cutted = true;
                    }
                    break;
                }
            }

            return 'Invalid value. Allow ' . $need . (($cutted) ? '...' : '.');
        }
        return true;
    }

    public static function checkSum($value, $params = null)
    {
        if ($value === null) return true;

        if (!is_double($value)) {
            return 'Invalid value. Allow double number.';
        }
        return true;
    }

    public static function checkMin($value, $params = null)
    {
        if ($value === null) return true;

        if ($value < $params) {
            return 'Value is less than ' . $params;
        }
        return true;
    }

    public static function checkMax($value, $params = null)
    {
        if ($value === null) return true;

        if ($value > $params) {
            return 'Value is greater than ' . $params;
        }
        return true;
    }

    public static function checkBetween($value, $params = null)
    {
        if ($value === null) return true;

        $limits = explode(',', $params);
        if (count($limits) < 2) {
            throw new ValidatorException("Invalid parameters for rule \"between\" ({$params})");
        }
        $limits = array_map(function($v) {
            return trim($v);
        }, $limits);

        $res = static::checkMin($value, $limits[0]);
        if ($res !== true) {
            return $res;
        }
        $res = static::checkMax($value, $limits[1]);
        if ($res !== true) {
            return $res;
        }
        return true;
    }

    public static function checkContainer($value, $className = null)
    {
        if (!$className) {
            throw new FilterException("Parameter className is required for rule container");
        }

        if ($value === null) return true;

        if (!($value instanceof $className)) {
            return "Isn't {$className}";
        }

        return true;
    }

    public static function checkContainerCollection($value, $className = null)
    {
        if (!$className) {
            throw new FilterException("Parameter className is required for rule containerCollection");
        }

        if ($value === null) return true;

        if (!($value instanceof ContainerCollection)) {
            return "Isn't container collection";
        }
        if ($value->count() > 0 && !($value->get(0) instanceof $className)) {
            return "Isn't collection of {$className}";
        }

        return true;
    }

    public static function checkDate($value, $params = null)
    {
        if ($value === null) return true;

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            return "Isn't correct date";
        }
        return true;
    }

    public static function checkParticipantType($value, $params = null)
    {
        return self::checkIn($value, '1|2');
    }

    public static function checkVehicleType($value, $params = null)
    {
        return self::checkIn($value, 'Легковое ТС|Грузовое ТС|Автобус|Микроавтобус|Спецтехника|Малотоннажное ТС|Троллейбус|Трамвай|Мотоцикл');
    }

    public static function checkDocType($value, $params = null)
    {
        return self::checkIn($value, 'Паспорт РФ|PASSPORT|DRIVING_LICENCE|ZAGRAN_PASSPORT|FOREIGN_PASSPORT|MILITARY_CARD'
            . '|REGISTRATION_CERTIFICATE|RESIDENTIAL_PERMIT|SOLDIER_IDENTIFY_CARD|PTS|DIAGNOSTIC_CARD|TALON_TECHOSMOTR|STS');
    }

    public static function checkLength($value, $params = null)
    {
        if ($value === null) return true;

        $limits = explode(',', $params);
        $limits = array_map(function($v) {
            return trim($v);
        }, $limits);

        if (is_array($value)) {
            if ($limits[0] != '' && count($value) < $limits[0]) {
                return 'Length of array is less than ' . $limits[0];
            }
            if (isset($limits[1]) && $limits[1] != '' && count($value) > $limits[1]) {
                return 'Length of array is greater than ' . $limits[1];
            }
        } elseif ($value instanceof ContainerCollection) {
            if ($limits[0] != '' && $value->count() < $limits[0]) {
                return 'Length of collection is less than ' . $limits[0];
            }
            if (isset($limits[1]) && $limits[1] != '' && $value->count() > $limits[1]) {
                return 'Length of collection is greater than ' . $limits[1];
            }
        } else {
            if ($limits[0] != '' && strlen($value) < $limits[0]) {
                return 'Length of string is less than ' . $limits[0];
            }
            if (isset($limits[1]) && $limits[1] != '' && strlen($value) > $limits[1]) {
                return 'Length of string is greater than ' . $limits[1];
            }
        }

        return true;
    }
}