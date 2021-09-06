<?php
declare(strict_types=1);

namespace Structure\Handlers;

class ObjectHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'class' => null,
    ];

    public function filter($value) : ?object
    {
        if (!is_object($value)) {
            return null;
        }
        if (
            $class = $this->getOption('class') and
            class_exists($class, false) and
            !$value instanceof $class
        ) {
            return null;
        }
        return $value;
    }

    public function default(string $default) : ?object
    {
        if(class_exists($default, false)){
            return new $default;
        }
        return null;
    }

}