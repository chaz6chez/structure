<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filter\Handle;

use Chaz\Filter\Filter;

class Strings extends Filter {

    /**
     * 默认配置
     * @var array
     */
    protected $defaultOptions = [
        'min' => 0,
        'max' => PHP_INT_MAX,
    ];

    /**
     * 过滤器实现
     * @param $var
     * @return null|string
     */
    public function filter($var){
        if (is_object($var) && method_exists($var, '__toString')) {
            $var = (string) $var;
        }
        if (!is_scalar($var)) {
            return null;
        }
        $var = (string) $var;
        if (self::$options['min'] > strlen($var)) {
            return null;
        } elseif (self::$options['max'] < strlen($var)) {
            return null;
        }
        return $var;
    }

    /**
     * 验证器实现
     * @param $var
     * @return string
     */
    public function validate($var){
        if (is_object($var) && method_exists($var, '__toString')) {
            $var = (string) $var;
        }
        return $var;
    }
}