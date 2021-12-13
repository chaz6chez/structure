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

        $this->assertEquals(serialize([
            'di' => 1,
            'eman[!]' => 'John',
            'xes[>]' => 'man'
        ]),serialize($struct->transfer(
            STRUCT_TRANSFER_OPERATOR,
            STRUCT_TRANSFER_MAPPING
        )->output()));
    }

    public function testTransferOperatorOutput(){
        $struct = new Output();

        $struct->create([
            'id' => 1,
            'name' => '123[!]',
            'sex' => '1.01[>]'
        ]);
        $this->assertEquals(serialize([
            'id' => 1,
            'name[!]' => 123,
            'sex[>]' => 1.01
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));

        $struct->create([
            'id' => 1,
            'name' => '123,456[<>]',
            'sex' => 'abc[>]'
        ]);
        $this->assertEquals(serialize([
            'id' => 1,
            'name[<>]' => [123,456],
            'sex[>]' => 'abc'
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));

        $struct->create([
            'id' => 1,
            'name' => '123[String],456[Float][<>]',
            'sex' => '1[Bool][>]'
        ]);
        $this->assertEquals(serialize([
            'id' => 1,
            'name[<>]' => ['123',456.0],
            'sex[>]' => true
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));

        $struct->create([
            'id' => 1,
            'name' => [
                [
                    '123[String]',
                    [
                        '1[Float]'
                    ]
                ],
                '0[Int]',
                '1[Bool]',
                'abc',
                '1',
                '1.11',
                true
            ],
            'sex' => '0[Bool][>]'
        ]);
        $this->assertEquals(serialize([
            'id' => 1,
            'name' => [
                [
                    '123',
                    [
                        1.0
                    ]
                ],
                0,
                true,
                'abc',
                1,
                1.11,
                true
            ],
            'sex[>]' => false
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));
    }

    public function testTransferSceneOutput(){
        $struct = new Output([
            'id' => 1,
            'name' => 'John',
            'sex' => 'man[>]'
        ],'method_1');

        $this->assertEquals(serialize([
            'id' => 1,
            'name' => '> [John] <',
            'sex[>]' => 'man'
        ]),serialize($struct->transfer(STRUCT_TRANSFER_OPERATOR)->output()));

        $this->assertEquals(serialize([
            'di' => 1,
            'eman' => '> [John] <',
            'xes[>]' => 'man'
        ]),serialize($struct->transfer(
            STRUCT_TRANSFER_MAPPING,
            STRUCT_TRANSFER_OPERATOR
        )->output()));

        $this->assertEquals(serialize([
            'di' => 1,
            'eman' => '> [John] <',
            'xes[>]' => 'man'
        ]),serialize($struct->transfer(
            STRUCT_TRANSFER_OPERATOR,
            STRUCT_TRANSFER_MAPPING
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
     * @operator[method_1] method:_transfer
     * @operator[method_2] method:\Structure\Tests\Output,_transfer
     */
    public $name;

    /**
     * @var
     * @ghost[not_full]
     * @mapping xes
     * @operator ture
     */
    public $sex;

    public static function _transfer($value): string
    {
        return "> [{$value}] <";
    }
}