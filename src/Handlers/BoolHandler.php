<?php
declare(strict_types=1);

namespace Structure\Handlers;

class BoolHandler extends AbstractHandler {

    protected $_defaultOptions = [
    ];

    public function filter($value) : ?bool
    {
        if(!is_bool($value)){
            return $this->setPosition('_type_');
        }
        return $value;
    }

    public function default(string $default) : bool
    {
        return boolval($default === 'true');
    }
}