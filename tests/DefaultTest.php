<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class DefaultTest extends TestCase {

    public function testInt(){
        $struct = new DefaultTag([],'int');
        $this->assertEquals(serialize([
            'name' => 123,
        ]),serialize($struct->output()));
    }

    public function testString(){
        $struct = new DefaultTag([],'string');
        $this->assertEquals(serialize([
            'name' => '123',
        ]),serialize($struct->output()));
    }

    public function testFloat(){
        $struct = new DefaultTag([],'float');
        $this->assertEquals(serialize([
            'name' => 1.223,
        ]),serialize($struct->output()));
    }

    public function testArray(){
        $struct = new DefaultTag([],'array');
        $this->assertEquals(serialize([
            'name' => ['value'],
        ]),serialize($struct->output()));
    }

    public function testMap(){
        $struct = new DefaultTag([],'map');
        $this->assertEquals(serialize([
            'name' => [
                'key' => 'value'
            ],
        ]),serialize($struct->output()));
    }

    public function testBool(){
        $struct = new DefaultTag([],'bool');
        $this->assertEquals(serialize([
            'name' => false,
        ]),serialize($struct->output()));
    }

    public function testObject(){
        $struct = new DefaultTag([],'object');
        $this->assertEquals(serialize([
            'name' => new DefaultTag(),
        ]),serialize($struct->output()));
    }

    public function testMethod(){
        $struct = new DefaultTag();
        $struct->scene('method_1');
        $this->assertEquals(serialize([
            'name' => '7788',
        ]),serialize($struct->output()));

        $struct->clean();
        $struct->scene('method_2');
        $this->assertEquals(serialize([
            'name' => '7788',
        ]),serialize($struct->output()));
    }

    public function testFunc(){
        $struct = new DefaultTag();
        $struct->scene('func');
        $this->assertEquals(serialize([
            'name' => get_current_user(),
        ]),serialize($struct->output()));
    }
}

class DefaultTag extends Struct {

    /**
     * @var
     * @default[int] int:123
     * @default[string] string:123
     * @default[float] float:1.223
     * @default[array] array:["value"]
     * @default[map] map:{"key":"value"}
     * @default[bool] bool:false
     * @default[object] object:\Structure\Tests\DefaultTag
     * @default[method_1] method:_get
     * @default[method_2] method:\Structure\Tests\DefaultTag,_get
     * @default[func] func:get_current_user
     */
    public $name;

    public static function _get(){
        return '7788';
    }
}