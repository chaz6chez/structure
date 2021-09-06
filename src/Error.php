<?php
declare(strict_types=1);

namespace Structure;

class Error {
    /**
     * @var string|null
     */
    protected $_field;

    /**
     * @var string|null
     */
    protected $_message;

    /**
     * @var string|null
     */
    protected $_code;

    /**
     * Error constructor.
     * @param null|string $field
     * @param null|string $message
     * @param string|null $code
     */
    public function __construct(?string $field, ?string $message, ?string $code = null)
    {
        $this->setField($field);
        $this->setMessage($message);
        $this->setCode($code);
    }

    /**
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->_field;
    }

    /**
     * @param string|null $field
     */
    public function setField(?string $field): void
    {
        $this->_field = $field;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->_message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->_message = $message;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->_code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(?string $code): void
    {
        $this->_code = $code;
    }
}