<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters\Handle;

use Chaz\Filters\Filter;

class URL extends Filter {
    
    protected $defaultOptions = [
        'path' => false,
        'query' => false,
    ];

    public function filter($var) {
        $flags = 0;
        if (self::$options['path']) {
            $flags |= FILTER_FLAG_PATH_REQUIRED;
        }
        if (self::$options['query']) {
            $flags |= FILTER_FLAG_QUERY_REQUIRED;
        }
        return filter_var($var, FILTER_VALIDATE_URL, $flags);
    }

}