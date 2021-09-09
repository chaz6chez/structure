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
            return $this->setPosition('_type_');
        }
        if (
            $class = $this->getOption('class') and
            class_exists($class, false) and
            !$value instanceof $class
        ) {
            return $this->setPosition('_class_');
        }
        return $value;
    }

    public function default(string $default) : ?object
    {
        try {
            if(class_exists($default, false)){
                return new $default;
            }
        }catch (\Throwable $throwable){

        }
        return null;
    }

}