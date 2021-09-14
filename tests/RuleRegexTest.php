<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleRegexTest extends TestCase {


    public function testSuccess(){
        $struct = new RuleRegex([
            'test' => '18523022302'
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => '18523022302'
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new RuleRegex([]);
        $struct->create([
            'test' => '188'
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_regex_',$struct->getError()->getPosition());
    }
}

class RuleRegex extends Struct {

    /**
     * @var
     * @rule regex,min:1,max:150,regex:/^1[3,4,5,6,7,8,9][\d]{9}$/|format error:code1
     */
    public $test;
}