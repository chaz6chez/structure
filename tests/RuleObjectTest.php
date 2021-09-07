<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleObjectTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleObject();
        $struct->create([
            'test' => new RuleObject()
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => new RuleObject()
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new RuleObject([]);
        $struct->create([
            'test' => [1,2,3]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_',$struct->getError()->getPosition());

        $struct->create([
            'test' => new RuleMap()
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_class_',$struct->getError()->getPosition());
    }
}

class RuleObject extends Struct {

    /**
     * @var
     * @rule object,class:\Structure\Tests\RuleObject|format error:code1
     */
    public $test;
}