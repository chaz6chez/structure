<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleStringTest extends TestCase {


    public function testSuccess(){
        $struct = new RuleString([
            'test' => '123'
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
    }

    public function testError(){
        $struct = new RuleString([]);
        $struct->create([
            'test' => 'a'
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_min_',$struct->getError()->getPosition());

        $struct->create([
            'test' => 'abcdefg'
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_max_',$struct->getError()->getPosition());

        $struct->create([
            'test' => 123
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_',$struct->getError()->getPosition());

    }
}

class RuleString extends Struct {

    /**
     * @var
     * @rule string,min:2,max:5,|format error:code1
     */
    public $test;
}