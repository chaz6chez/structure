<?php
declare(strict_types=1);

namespace Structure\Tests;
use PHPUnit\Framework\TestCase;
use Structure\Struct;

class RuleURLTest extends TestCase {

    public function testSuccess(){
        $struct = new RuleURL([
            'test' => 'http://php.com'
        ]);
        $this->assertEquals(false,$struct->validate());
        $this->assertEquals(false,$struct->hasError());
        $this->assertEquals(null,$struct->getError()->getMessage());
        $this->assertEquals(null,$struct->getError()->getCode());
        $this->assertEquals(serialize([
            'test' => 'http://php.com'
        ]),serialize($struct->output()));
    }
}

class RuleURL extends Struct {

    /**
     * @var
     * @rule url,path:false,query:false|format error:code1
     */
    public $test;
}