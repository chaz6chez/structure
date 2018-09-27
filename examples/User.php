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
     * @rule string,max:10|名字字节长度不可超过10
     */
    public $name;

    /**
     * @var
     * @required true|性别不能为空
     */
    public $sex;

    /**
     * @var
     * @required[check] true|验证时，年龄不能为空
     */
    public $age;
}