<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Map extends Filter {
    
    protected $_defaultOptions = [
        'filters' => [],
    ];

    public function filter($var) {
        if (!is_object($var) && !is_array($var)) {
            return null;
        }
        $isArray = is_array($var) || $var instanceof \ArrayAccess;

        foreach ($this->_options['filters'] as $key => $filter) {
            $filter = self::factory($filter);
            if ($isArray) {
                if (!isset($var[$key])) {
                    $var[$key] = null;
                }
                $var[$key] = $filter->filter($var[$key]);
            } else {
                if (!isset($var->$key)) {
                    $var->$key = null;
                }
                $var->$key = $filter->filter($var->$key);
            }
        }
        return $var;
    }

    public function validate($var) {
        if (!is_object($var) && !is_array($var)) {
            return false;
        }
        if (is_object($var)) {
            return $var == $this->filter($var);
        }
        return $var == $this->filter($var);
    }

}