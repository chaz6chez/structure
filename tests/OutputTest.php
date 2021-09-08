<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

class OutputTest extends TestCase {

    public function testOutput(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]);
        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]),serialize($struct->output()));

        $struct->scene('not_full');

        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]),serialize($struct->output(true)));

        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
        ]),serialize($struct->output()));
    }

    public function testGhostOutput(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ],'not_full');
        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
        ]),serialize($struct->output()));

        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]),serialize($struct->output(true)));
    }

    public function testTransferOutput(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]);
        $this->assertEquals(serialize([
            'di' => 1,
            'eman' => 'John',
            'xes' => 'man'
        ]),serialize($struct->transfer(STRUCT_TRANSFER_MAPPING)->output()));

        $struct->create([
            'id' => 1,
            'name' => 'John[!]',
            'sex' => 'man[>]'
        ]);

        $this->assertEquals(serialize([
            'id' => 1,
            'name[!]' => 'John',
            'sex[>]' => 'man'
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));

        $this->assertEquals(serialize([
            'di' => 1,
            'eman[!]' => 'John',
            'xes[>]' => 'man'
        ]),serialize($struct->transfer(
            STRUCT_TRANSFER_MAPPING,
            STRUCT_TRANSFER_OPERATOR
        )->output()));
    }

    public function testFilterOutput(){
        $struct = new Output([
            'id' => 0,
            'name' => '',
            'sex' => 'man'
        ]);
        $this->assertEquals(serialize([
            'name' => '',
            'sex' => 'man'
        ]),serialize($struct->filter(STRUCT_FILTER_ZERO)->output()));

        $this->assertEquals(serialize([
            'id' => 0,
            'sex' => 'man'
        ]),serialize($struct->filter(STRUCT_FILTER_EMPTY)->output()));

        $this->assertEquals(serialize([
            'sex' => 'man'
        ]),serialize($struct->filter(
            STRUCT_FILTER_EMPTY,
            STRUCT_FILTER_ZERO
        )->output()));

        $struct->id = null;
        $this->assertEquals(serialize([
            'name' => '',
            'sex' => 'man'
        ]),serialize($struct->filter(STRUCT_FILTER_NULL)->output()));
    }

    public function testFilterOutputByKey(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]);
        $this->assertEquals(serialize([
            'sex' => 'man'
        ]),serialize($struct->filter(STRUCT_FILTER_KEY)->output()));

        $this->assertEquals(serialize([
            'id' => 1,
            'name' => 'John',
        ]),serialize($struct->filter(STRUCT_FILTER_KEY_REVERSE)->output()));
    }

    public function testFilterOutputByOperator(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man'
        ]);
        $this->assertEquals(serialize([
            'name' => 'John',
            'sex' => 'man'
        ]),serialize($struct->filter(STRUCT_FILTER_OPERATOR_REVERSE)->output()));

        $this->assertEquals(serialize([
            'id' => 1,
        ]),serialize($struct->filter(STRUCT_FILTER_OPERATOR)->output()));
    }
}

class Output extends Struct {

    /**
     * @var
     * @key true
     * @mapping di
     */
    public $id;

    /**
     * @var
     * @key true
     * @mapping eman
     * @operator ture
     */
    public $name;

    /**
     * @var
     * @ghost[not_full]
     * @mapping xes
     * @operator ture
     */
    public $sex;
}