<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters\Handle;

use Chaz\Filters\Filter;

class Chain extends Filter {
    
    protected $defaultOptions = [
        'filters' => [],
    ];

    public function filter($var) {
        foreach (self::$options['filters'] as $filter) {
            $filter = self::factory($filter);
            $var = $filter->filter($var);
        }
        return $var;
    }

    public function validate($var) {
        foreach (self::$options['filters'] as $filter) {
            $filter = self::factory($filter);
            if (!$filter->validate($var)) {
                return false;
            }
            $var = $filter->filter($var);
        }
        return true;
    }

}