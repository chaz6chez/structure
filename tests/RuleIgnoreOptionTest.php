<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleIgnoreOptionTest extends TestCase {


    public function testSuccess(){
        $struct = new RuleStringIgnoreOption([
            'test' => '123'
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => '123'
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new RuleStringIgnoreOption([]);
        $struct->create([
            'test' => 123
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_',$struct->getError()->getPosition());

    }
}
class RuleStringIgnoreOption extends Struct {

    /**
     * @var
     * @rule string|format error:code1
     */
    public $test;
}