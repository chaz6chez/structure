<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/27           #
# -------------------------- #
namespace Example;

use Structure\Struct;

class User extends Struct{

    public $id;

    /**
     * @var
     */
    public $name;

    /**
     * @var
     * @mapping ass
     * @required true
     * @rule string,min:10,max:20|aaaaa
     */
    public $sex;

    /**
     * @var
     * @mapping key
     * @operator true
     */
    public $age;
}