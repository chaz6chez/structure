<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Test;

use Chaz\Structure;

class Check extends Structure {
    /**
     * @var
     * @rule string,max:3|我操
     */
    public $check;
}