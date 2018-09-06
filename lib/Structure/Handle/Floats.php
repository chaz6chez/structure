<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Floats extends Filter {
    
    protected $defaultOptions = [
        'min' => null,
        'max' => null,
    ];

    public function filter($var) {
        if (!is_numeric($var)) {
            return null;
        }
        $var = (float) $var;
        if (null !== self::$options['min'] && self::$options['min'] > $var) {
            return self::$options['min'];
        } elseif (null !== self::$options['max'] && self::$options['max'] < $var) {
            return self::$options['max'];
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