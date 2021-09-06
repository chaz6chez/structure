<?php
declare(strict_types=1);

namespace Structure\Handlers;

class IntHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX,
    ];

    public function filter($value) : ?int
    {
        if (!is_int($value)) {
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

    public function default(string $default) : ?int
    {
        return is_numeric($default) ? (int)$default : null;
    }
}