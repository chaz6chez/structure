<?php
declare(strict_types=1);

namespace Structure\Tests;

use PHPUnit\Framework\TestCase;
use Structure\Struct;

abstract class BaseTestCase extends TestCase
{
    /**
     * @var Struct
     */
    private $_struct;

    public function struct() : Struct
    {
        if($this->_struct instanceof Struct){
            return $this->_struct;
        }
        throw new \InvalidArgumentException('Struct Error.');
    }

    /**
     * @param Struct $struct
     */
    public function setStruct(Struct $struct)
    {
        $this->_struct = $struct;
    }
}