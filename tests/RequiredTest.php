<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RequiredTest extends TestCase {

    public function testSuccess(){
        $struct = new Required([
            'name' => ''
        ]);
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(null,$struct->getError()->getPosition());
        $this->assertEquals(null,$struct->getError()->getPosition());
        $this->assertEquals(serialize([
            'id' => null,
            'name' => ''
        ]),serialize($struct->output()));

        $struct = new Required([
            'name' => 0
        ]);
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(null,$struct->getError()->getPosition());
        $this->assertEquals(serialize([
            'id' => null,
            'name' => 0
        ]),serialize($struct->output()));
    }

    public function testError(){
        $struct = new Required();
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('name cannot be empty',$struct->getError()->getMessage());
        $this->assertEquals('1001',$struct->getError()->getCode());

        $struct = new Required([
            'name' => null
        ]);
        $this->assertEquals(true,$struct->hasError());
        $this->assertEquals('name cannot be empty',$struct->getError()->getMessage());
        $this->assertEquals('1001',$struct->getError()->getCode());
    }
}

class Required extends Struct {

    public $id;
    /**
     * @var
     * @required true|name cannot be empty:1001
     */
    public $name;
}