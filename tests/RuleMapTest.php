<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleMapTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleMap();
        $struct->create([
            'test' => [
                '1.1' => '1',
                '1.2' => '1',
                '1.3' => '1',
                '1.4' => '1',
            ]
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => [
                '1.1' => '1',
                '1.2' => '1',
                '1.3' => '1',
                '1.4' => '1',
            ]
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new RuleMap([]);
        $struct->create([
            'test' => [
                '1.1' => '1',
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_min_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                '1.1' => '1',
                '2.1' => '1',
                '3.1' => '1',
                '4.1' => '1',
                '5.1' => '1',
                '6.1' => '1'
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_max_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                '1',
                '2',
                '3',
                '4',
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_type_map_',$struct->getError()->getPosition());

        $struct->create([
            'test' => [
                1 => '1',
                2 => '1',
                '3.1' => '1',
                '4.1' => '1',
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_keys_',$struct->getError()->getPosition());


        $struct->create([
            'test' => [
                '1.1' => 1,
                '2.1' => 2,
                '3.1' => '1',
                '4.1' => '1',
            ]
        ]);
        $this->assertEquals(true,$struct->validate(true));
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('_values_',$struct->getError()->getPosition());
    }
}

class RuleMap extends Struct {

    /**
     * @var
     * @rule map,min:2,max:5,keys:string,values:string|format error:code1
     */
    public $test;
}