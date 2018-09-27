<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class IP extends Filter {

    protected $_filterName = 'ip 过滤器';
    protected $_defaultOptions = [
        'ipv4' => true,
        'ipv6' => true,
        'private' => true,
        'reserved' => true,
    ];

    public function filter($var) {
        $flags = 0;
        if ($this->_options['ipv4']) {
            $flags |= FILTER_FLAG_IPV4;
        }
        if ($this->_options['ipv6']) {
            $flags |= FILTER_FLAG_IPV6;
        }
        if (!$this->_options['private']) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if (!$this->_options['reserved']) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }
        return filter_var($var, FILTER_VALIDATE_IP, $flags);
    }

}