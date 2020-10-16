<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/27           #
# -------------------------- #
namespace Example;

use Structure\Struct;

class User extends Struct{
    protected $register = [];

    public $id;

    /**
     * @vars
     * @default method:_lock
     */
    public $name;
    public function _lock(){
        return 123;
    }
    public $sex;
    public $age;
}