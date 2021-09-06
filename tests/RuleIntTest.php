<?php
declare(strict_types=1);

namespace Structure\Tests;
use Structure\Struct;

class RuleIntTest extends BaseTestCase {

    protected function setUp() : void
    {
        $this->setStruct(new RuleInt());
    }

//    public function testIntSuccess(){
//        $struct = new RuleInt([
//            'test' => 4
//        ]);
//        $struct->validate();
//        $this->assertEquals(false,$struct->hasError());
//    }

    public function testIntTypeError(){
        $struct = new RuleInt([
            'test' => '4'
        ]);
        $struct->validate();exit;
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type error',$struct->getError());
    }

    public function testIntMinError(){
        $struct = new RuleInt([
            'test' => 1
        ]);
        $struct->validate();
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type min',$struct->getError());
    }

    public function testIntMaxError(){
        $struct = new RuleInt([
            'test' => 6
        ]);
        $struct->validate();
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type max',$struct->getError());
    }

    public function testIntFormatError(){
        $struct = new RuleInt([
            'test' => 6
        ],'int');
        $struct->validate();
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type format error',$struct->getError());
    }
}

class RuleInt extends Struct {

    /**
     * @var
     * @rule int|type error
     * @rule int,min:3|type min
     * @rule int,max:5|type max
     * @rule[int] int,min:3,max:5|type format error
     */
    public $test;
}