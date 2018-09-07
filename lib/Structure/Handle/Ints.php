<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

defined('PHP_INT_MIN') or define('PHP_INT_MIN', ~PHP_INT_MAX);

class Ints extends Filter {
    
    protected $_defaultOptions = [
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX,
    ];

    public function filter($var) {
        if (!is_numeric($var)) {
            return null;
        }
        $var = (int) $var;
        if ($this->_options['min'] > $var) {
            return $this->_options['min'];
        } elseif ($this->_options['max'] < $var) {
            return $this->_options['max'];
        }
        return $var;
    }

    public function validate($var) {
        if (!is_numeric($var)) {
            return false;
        }
        return parent::validate($var);
    }
}