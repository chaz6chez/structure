<?php
declare(strict_types=1);

namespace Structure\Handlers;

class RegexHandler extends StringHandler {

    protected $_defaultOptions = array(
        'min' => 0,
        'max' => PHP_INT_MAX,
        'regex' => '/.?/',
    );

    public function filter($value) : ?string
    {
        $value = parent::filter($value);
        if (!preg_match($this->getOption('regex','/.?/'), $value)) {
            return null;
        }
        return $value;
    }

}