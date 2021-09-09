<?php
declare(strict_types=1);

namespace Structure\Exceptions;

use RuntimeException;
use Throwable;

class StructureException extends RuntimeException {

    protected $_position;

    public function setPosition(string $position): void
    {
        $this->_position = $position;
    }

    public function getPosition() : ?string
    {
        return $this->_position;
    }

    public function __construct(string $position, Throwable $previous = null)
    {
        $this->setPosition($position);
        parent::__construct("Structure Exception [{$position}]", -666, $previous);
    }

}