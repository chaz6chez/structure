<?php
declare(strict_types=1);

namespace Tests;

use Structure\Struct;

class DemoStruct extends Struct
{
    /**
     * @var
     * @rule[array_string_array] array,min:5,max:10,keys:string,values:array|rule_test format error
     * @rule[array_int_object] array,min:5,max:10,keys:int,values:object|rule_test format error
     * @rule[array] array,min:5,max:10|rule_test format error
     * @rule[assoc_string] assoc,min:5,max:10,values:string|rule_test format error
     * @rule[assoc_int] assoc,min:5,max:10,values:int|rule_test format error
     * @rule[assoc] assoc,min:5,max:10|rule_test format error
     * @rule[string] string,min:5,max:10|rule_test format error
     * @rule[int] int,min:5,max:10|rule_test format error
     * @rule[bool] bool|rule_test format error
     * @rule[float] float,min:5,max:10|rule_test format error
     * @rule[ip] ip,ipv4:true,ipv6:true,private:true,reserved:true|rule_test format error
     * @rule[object] object,class:/Tests/DemoStruct|rule_test format error
     * @rule[url] url,path:true,query:true|rule_test format error
     * @rule[regex] regex,min:5,max:10,regex:/.?/|rule_test format error
     * @rule[pool] pool,filters:[]|rule_test format error
     * @rule[map] map,filters:[]|rule_test format error
     * @rule[chain] chain,filters:[]|rule_test format error
     */
    public $rule_test;

    public $require_test;

    public $default_test;

    public $mapping_test;

    public $key_test;

    public $operator_test;

    public $ghost_test;

    public $skip_test;
}