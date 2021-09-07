<?php
declare(strict_types=1);

namespace Structure\Handlers;

class StringHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
    ];

    /**
     * @inheritDoc
     */
    public function filter($value) : ?string
    {
        if (!is_string($value)) {
            return $this->setPosition('_type_');
        }
        if ($this->getOption('min') > strlen($value)) {
            return $this->setPosition('_min_');
        }
        if ($this->getOption('max') < strlen($value)) {
            return $this->setPosition('_max_');
        }
        return $value;
    }

    public function default(string $default) : string
    {
        return trim($default);
    }
}