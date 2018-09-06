<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters\Handle;

use Chaz\Filters\Filter;

defined('PHP_INT_MIN') or define('PHP_INT_MIN', ~PHP_INT_MAX);

class Ints extends Filter {
    
    protected $defaultOptions = [
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX,
    ];

    public function filter($var) {
        if (!is_numeric($var)) {
            return null;
        }
        $var = (int) $var;
        if (self::$options['min'] > $var) {
            return self::$options['min'];
        } elseif (self::$options['max'] < $var) {
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