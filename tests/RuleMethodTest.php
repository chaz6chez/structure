<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Exceptions\StructureException;
use Structure\Struct;

class RuleMethodTest extends TestCase {


    public function testMethodOne(){
        $struct = new RuleMethod([
            'test' => true
        ],'method_1');
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => true
        ]),serialize($struct->output()));

        $struct = new RuleMethod([
            'test' => false
        ],'method_1');
        $this->assertEquals(true,$struct->validate());
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('format error',$struct->getError()->getMessage());
        $this->assertEquals('code1',$struct->getError()->getCode());
    }

    public function testMethodTwo(){
        $struct = new RuleMethod([
            'test' => true
        ],'method_2');
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => true
        ]),serialize($struct->output()));

        $struct = new RuleMethod([
            'test' => false
        ],'method_2');
        $this->assertEquals(true,$struct->validate());
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('format error',$struct->getError()->getMessage());
        $this->assertEquals('code1',$struct->getError()->getCode());
    }

    public function testMethodThree(){
        $struct = new RuleMethod([
            'test' => true
        ],'method_3');
        $this->expectException(StructureException::class);

        $this->assertEquals(true,$struct->validate());
        $this->assertEquals(true,$struct->hasError());
    }

    public function testMethodFour(){
        $struct = new RuleMethod([
            'test' => true
        ],'method_4');
//        $this->expectException(StructureException::class);

//        $this->assertEquals(true,$struct->validate());
//        $this->assertEquals(true,$struct->hasError());

        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());

    }

}

class RuleMethod extends Struct {

    /**
     * @var
     * @rule[method_1] method:_set|format error:code1
     * @rule[method_2] method:\Structure\Tests\RuleMethod,_set|format error:code1
     * @rule[method_3] method:_set_throwable|format error:code1
     * @rule[method_4] method:\Structure\Tests\RuleMethod,_throwable_1|format error:code1
     */
    public $test;

    public static function _set($value){
        return $value;
    }

    public function _set_throwable(){
        throw new \RuntimeException('runtime_exception','110');
    }

    public function _throwable_1($value)
    {
        return $value;
    }
}