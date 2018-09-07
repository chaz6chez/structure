<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class URL extends Filter {
    
    protected $_defaultOptions = [
        'path' => false,
        'query' => false,
    ];

    public function filter($var) {
        $flags = 0;
        if ($this->_options['path']) {
            $flags |= FILTER_FLAG_PATH_REQUIRED;
        }
        if ($this->_options['query']) {
            $flags |= FILTER_FLAG_QUERY_REQUIRED;
        }
        return filter_var($var, FILTER_VALIDATE_URL, $flags);
    }

}