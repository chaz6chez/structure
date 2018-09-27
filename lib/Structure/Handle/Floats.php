<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Floats extends Filter {

    protected $_filterName = 'float 过滤器';
    protected $_defaultOptions = [
        'min' => null,
        'max' => null,
    ];

    public function filter($var) {
        if (!is_numeric($var)) {
            return null;
        }
        $var = (float) $var;
        if (null !== $this->_options['min'] && $this->_options['min'] > $var) {
            return $this->_options['min'];
        } elseif (null !== $this->_options['max'] && $this->_options['max'] < $var) {
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