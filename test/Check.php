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
     * @required true|这是a
     * @ghost
     */
    public $a;

    /**
     * @var
     * @required true|这是b
     */
    public $b;
}