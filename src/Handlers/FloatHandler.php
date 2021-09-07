<?php
declare(strict_types=1);

namespace Structure\Handlers;

class FloatHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => PHP_FLOAT_MIN,
        'max' => PHP_FLOAT_MAX,
        'scale' => null
    ];

    public function filter($value) : ?float
    {
        if (!is_float($value)) {
            return $this->setPosition('_type_');
        }
        if ($this->getOption('min') > $value) {
            return $this->setPosition('_min_');
        }
        if ($this->getOption('max') < $value) {
            return $this->setPosition('_max_');
        }
        if (
            $scale = $this->getOption('scale') and
            (int)$scale < strlen(substr(strrchr((string)$value, '.'), 1))
        ){
            return $this->setPosition('_scale_');
        }
        return $value;
    }

    public function default(string $default) : ?float
    {
        return is_numeric($default) ? floatval($default) : null;
    }
}