<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleArrayTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleArray();
        $struct->test = [
            '1',
            '2',
            '3',
            '4'
        ];
        $this->assertEquals(false,$struct->validate(false));
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
    }

    public function testError(){
        $struct = new RuleArray([]);
        $struct->create([
            'test' => [
                '1'
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_min_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                '1',
                '2',
                '3',
                '4',
                '5',
                '6'
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_max_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_array_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                1,
                2,
                3,
                '4'
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_values_',$struct->getError()->getPosition());
    }
}

class RuleArray extends Struct {

    /**
     * @var
     * @rule array,min:2,max:5,values:string|format error:code1
     */
    public $test;
}