<?php
declare(strict_types=1);

namespace Structure\Handlers;

class FloatHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => PHP_FLOAT_MIN,
        'max' => PHP_FLOAT_MAX,
    ];

    public function filter($value) : ?float
    {
        if (!is_float($value)) {
            return null;
        }
        if ($this->getOption('min') > $value) {
            return null;
        }
        if ($this->getOption('max') < $value) {
            return null;
        }
        return $value;
    }

    public function default(string $default) : ?float
    {
        return is_numeric($default) ? floatval($default) : null;
    }
}