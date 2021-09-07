<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleFloatTest extends TestCase {


    public function testSuccess(){
        $struct = new RuleFloat([
            'test' => 4.21
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
    }

    public function testError(){
        $struct = new RuleFloat([]);
        $struct->create([
            'test' => 1.21
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_min_',$struct->getError()->getPosition());

        $struct->create([
            'test' => 11.21
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_max_',$struct->getError()->getPosition());

        $struct->create([
            'test' => 4
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_',$struct->getError()->getPosition());

        $struct->create([
            'test' => 4.33333
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_scale_',$struct->getError()->getPosition());
    }
}

class RuleFloat extends Struct {

    /**
     * @var
     * @rule float,min:3,max:10,scale:3,|format error:code1
     */
    public $test;
}