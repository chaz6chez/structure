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
     * @skip
     * @mapping
     * @rule string,max:10|ssss
     * @rule[abc] string,max:10|ssxx
     * @rule[abc] string,max:10|sscxx
     */
    public $name;
    public $sex;
    public $age;
}