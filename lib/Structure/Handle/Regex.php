<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

class Regex extends Strings {
    
    protected $_defaultOptions = array(
        'min' => 0,
        'max' => PHP_INT_MAX,
        'regex' => '/.?/',
    );

    public function filter($var) {
        $var = parent::filter($var);
        if (!preg_match($this->_options['regex'], $var)) {
            return null;
        }
        return $var;
    }

}