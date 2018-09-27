<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Arrays extends Filter {

    protected $_filterName = 'array 过滤器';
    protected $_defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
        'keys' => null,
        'values' => null,
    ];

    public function filter($var) {
        if (!is_array($var)) {
            return null;
        }
        $count = count($var);
        if ($this->_options['min'] > $count) {
            return null;
        } elseif ($this->_options['max'] < $count) {
            return null;
        }
        if ($this->_options['keys']) {
            $filter = self::factory($this->_options['keys']);
            foreach ($var as $key => $value) {
                if (!$filter->validate($key)) {
                    unset($var[$key]);
                }
            }
        }
        if ($this->_options['values']) {
            $filter = self::factory($this->_options['values']);
            foreach ($var as $key => $value) {
                if (!$filter->validate($value)) {
                    unset($var[$key]);
                }
            }
        }
        return $var;
    }

    public function validate($var) {
        if (!is_array($var)) {
            return false;
        }
        return parent::validate($var);
    }
}
