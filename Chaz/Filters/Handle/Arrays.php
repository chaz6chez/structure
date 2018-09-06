<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters\Handle;

use Chaz\Filters\Filter;

class Arrays extends Filter {

    protected $defaultOptions = [
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
        if (self::$options['min'] > $count) {
            return null;
        } elseif (self::$options['max'] < $count) {
            return null;
        }
        if (self::$options['keys']) {
            $filter = self::factory(self::$options['keys']);
            foreach ($var as $key => $value) {
                if (!$filter->validate($key)) {
                    unset($var[$key]);
                }
            }
        }
        if (self::$options['values']) {
            $filter = self::factory(self::$options['values']);
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
