<?php
declare(strict_types=1);
namespace Structure\Handlers;

use ArrayAccess;
use Structure\Handler;

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
        if (!is_array($value) and !$value instanceof ArrayAccess) {
            return $this->setPosition('_type_');
        }
        $value = (array)$value;
        $count = count($value);
        if(!boolval(array_keys($value) !== range(0, $count - 1))){
            return $this->setPosition('_type_map_');
        }
        if ($this->getOption('min') > $count) {
            return $this->setPosition('_min_');
        }
        if ($this->getOption('max') < $count) {
            return $this->setPosition('_max_');
        }
        if ($this->getOption('keys')) {
            $filter = Handler::factory($this->getOption('keys'));
            foreach ($value as $k => $v) {
                if (!$filter->validate($k)) {
                    return $this->setPosition('_keys_');
                }
            }
        }
        if ($this->getOption('values')) {
            $filter = Handler::factory($this->getOption('values'));
            foreach ($value as $k => $v) {
                if (!$filter->validate($v)) {
                    return $this->setPosition('_values_');
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
        $array = @json_decode($default,true);
        if(json_last_error() === JSON_ERROR_NONE and $this->filter($array)){
            return (array) $array;
        }
        return null;
    }
}
