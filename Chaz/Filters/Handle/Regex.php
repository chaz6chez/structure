<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters\Handle;

class Regex extends Strings {
    
    protected $defaultOptions = array(
        'min' => 0,
        'max' => PHP_INT_MAX,
        'regex' => '/.?/',
    );

    public function filter($var) {
        $var = parent::filter($var);
        if (!preg_match(self::$options['regex'], $var)) {
            return null;
        }
        return $var;
    }

}