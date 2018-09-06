<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Test;

use Structure\Struct;

class Check extends Struct {
    /**
     * @var
     * @rule string,max:3|我操a
     */
    public $a;

    /**
     * @var
     * @rule int,max:2|我操b
     */
    public $b;
}