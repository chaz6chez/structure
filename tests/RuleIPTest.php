<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleIPTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleIP([
            'test' => '192.168.1.1'
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => '192.168.1.1'
        ]),serialize($struct->output()));
    }
}

class RuleIP extends Struct {

    /**
     * @var
     * @rule ip,ipv4:true,ipv6:true,private:false,reserved:fasle|format error:code1
     */
    public $test;
}