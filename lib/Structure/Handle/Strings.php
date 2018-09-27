<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Strings extends Filter {

    protected $_filterName = 'string 过滤器';
    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
    ];

    /**
     * 过滤器实现
     * @param $var
     * @return null|string
     */
    public function filter($var){
        if (is_object($var) && method_exists($var, '__toString')) {
            $var = (string) $var;
        }
        if (!is_scalar($var)) {
            return null;
        }
        $var = (string) $var;
        if ($this->_options['min'] > strlen($var)) {
            return null;
        } elseif ($this->_options['max'] < strlen($var)) {
            return null;
        }
        return $var;
    }

    public function validate($var){
        if (is_object($var) && method_exists($var, '__toString')) {
            $var = (string) $var;
        }
        return parent::validate($var);
    }
}