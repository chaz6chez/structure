<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleIntTest extends TestCase {

    /**
     *
     */
    public function testSuccess(){
        $struct = new RuleInt([
            'test' => 4
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => 4
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new RuleInt([
            'test' => 6
        ]);
        $this->assertEquals(true,$struct->validate());
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type format error',$struct->getError()->getMessage());
        $this->assertEquals('code1',$struct->getError()->getCode());
    }

    public function testErrorPosition(){
        $struct = new RuleInt([]);
        $struct->create([
            'test' => 1
        ])->validate(true);
        $this->assertEquals('_min_',$struct->getError()->getPosition());
        $struct->create([
            'test' => 11
        ])->validate(true);
        $this->assertEquals('_max_',$struct->getError()->getPosition());
        $struct->create([
            'test' => '11'
        ])->validate(true);
        $this->assertEquals('_type_',$struct->getError()->getPosition());
    }

    public function testSceneSuccess(){
        $struct = new RuleInt([
            'test' => 9
        ],'int');
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());

    }

    public function testSceneError(){
        $struct = new RuleInt([
            'test' => 11
        ],'int');
        $this->assertEquals(true,$struct->validate());
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('type format error',$struct->getError()->getMessage());
        $this->assertEquals('code2',$struct->getError()->getCode());

    }
}

class RuleInt extends Struct {

    /**
     * @var
     * @rule int,min:3,max:5|type format error:code1
     * @rule[int] int,min:5,max:10|type format error:code2
     */
    public $test;
}