<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/27           #
# -------------------------- #
namespace Example;

use Structure\Struct;

class User2 extends Struct{
    protected $register = [];

    /**
     * @vars
     * @skip[aa]
     * @mapping
     * @rule string,max:10|ssss
     * @rule[abc] string,max:10|ssxx
     * @rule[abc] string,max:10|sscxx
     */
    public $sex;
}