<?php
declare(strict_types=1);

namespace Structure\Handlers;

class ArrayHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
        'values' => null,
    ];

    public function filter($value) : ?array
    {
        if (!is_array($value)) {
            return null;
        }
        $count = count($value);
        if(!boolval(array_keys($value) !== range(0, $count - 1))){
            return null;
        }
        if ($this->getOption('min') > $count) {
            return null;
        }
        if ($this->getOption('max') < $count) {
            return null;
        }
        if ($this->getOption('values')) {
            $filter = new self($this->getOption('values'));
            foreach ($value as $k => $v) {
                if (!$filter->validate($k)) {
                    unset($value[$k]);
                }
            }
        }
        return $value;
    }

    public function default(string $default) : ?array
    {
        $array = @json_decode($default);
        if(json_last_error() == JSON_ERROR_NONE and $this->filter($array)){
            return (array) $array;
        }
        return null;
    }
}
