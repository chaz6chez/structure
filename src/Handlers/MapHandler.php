<?php
declare(strict_types=1);
namespace Structure\Handlers;

use stdClass;

class MapHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
        'keys' => null,
        'values' => null,
    ];

    /**
     * @inheritDoc
     */
    public function filter($value) : ?array
    {
        if (!is_array($value) and !$value instanceof stdClass) {
            return null;
        }
        $value = (array)$value;
        $count = count($value);
        if ($this->getOption('min') > $count) {
            return null;
        }
        if ($this->getOption('max') < $count) {
            return null;
        }
        if ($this->getOption('keys')) {
            $filter = new self($this->getOption('keys'));
            foreach ($value as $k => $v) {
                if (!$filter->validate($k)) {
                    unset($value[$k]);
                }
            }
        }
        if ($this->getOption('values')) {
            $filter = new self($this->getOption('values'));
            foreach ($value as $k => $v) {
                if (!$filter->validate($v)) {
                    unset($value[$k]);
                }
            }
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function validate($value): bool
    {
        return ($this->filter($value) === null) ? false : true;
    }

    /**
     * @inheritDoc
     */
    public function default(string $default) : ?array
    {
        $array = @json_decode($default);
        if(json_last_error() == JSON_ERROR_NONE and $this->filter($array)){
            return (array) $array;
        }
        return null;
    }
}
