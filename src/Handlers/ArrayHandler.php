<?php
declare(strict_types=1);

namespace Structure\Handlers;

use Structure\Handler;
use ArrayAccess;
use InvalidArgumentException;

class ArrayHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
        'values' => null,
    ];

    public function filter($value) : ?array
    {
        if (!is_array($value) and !$value instanceof ArrayAccess) {
            return $this->setPosition('_type_');
        }
        $value = (array)$value;
        $count = count($value);
        if(boolval(array_keys($value) !== range(0, $count - 1))){
            return $this->setPosition('_type_array_');
        }
        if ($this->getOption('min') > $count) {
            return $this->setPosition('_min_');
        }
        if ($this->getOption('max') < $count) {
            return $this->setPosition('_max_');
        }
        if ($this->getOption('values')) {
            try {
                $filter = Handler::factory($this->getOption('values'));
                foreach ($value as $v) {
                    if (!$filter->validate($v)) {
                        return $this->setPosition('_values_');
                    }
                }
            }catch (InvalidArgumentException $exception){}

        }
        return $value;
    }

    public function default(string $default) : ?array
    {
        $array = @json_decode($default,true);
        if(json_last_error() === JSON_ERROR_NONE and $this->filter($array)){
            return (array) $array;
        }
        return null;
    }
}
