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
     * @required true|aav
     * @rule string,min:10,max:20|aaaaa
     * @mapping aa
     * @mapping[ab] ab
     */
    public $sex;

    public $age;
}