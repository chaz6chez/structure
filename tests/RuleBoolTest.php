<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleBoolTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleBool([]);
        $struct->create([
            'test' => true
        ]);
        $this->assertEquals(false,$struct->validate(true));
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => true
        ]),serialize($struct->output()));

        $struct->create([
            'test' => false
        ]);
        $this->assertEquals(false,$struct->validate(true));
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
    }

    public function testError(){
        $struct = new RuleBool([]);
        $struct->create([
            'test' => 123
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());

        $struct->create([
            'test' => 'false'
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
    }
}

class RuleBool extends Struct {

    /**
     * @var
     * @rule bool,true|format error:code1
     */
    public $test;
}