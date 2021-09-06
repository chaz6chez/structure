<?php
declare(strict_types=1);

namespace Structure\Handlers;

class BoolHandler extends AbstractHandler {

    protected $_defaultOptions = [
    ];

    public function filter($value) : ?bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function default(string $default) : bool
    {
        return boolval($default === 'true');
    }
}