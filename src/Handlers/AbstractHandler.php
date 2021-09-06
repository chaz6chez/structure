<?php
declare(strict_types=1);

namespace Structure\Handlers;

use Structure\Protocols\HandlerInterface;

abstract class AbstractHandler implements HandlerInterface {

    protected $_defaultOptions = [];
    protected $_options = [];

    /**
     * Struct constructor.
     * @param array|null $options
     */
    final public function __construct(?array $options = null) {
        if($options){
            $this->setOptions($options);
        }
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options) : void
    {
        $this->_options = array_merge($this->_defaultOptions, $options);
    }

    /**
     * @return array
     */
    public function getOptions() : array
    {
        return $this->_options;
    }

    /**
     * @param string $field
     * @param null $default
     * @return mixed|null
     */
    public function getOption(string $field, $default = null)
    {
        return isset($this->_options[$field]) ? $this->_options[$field] : $default;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value): bool
    {
        return $this->filter($value) === null ? false : true;
    }
}