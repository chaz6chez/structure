<?php
declare(strict_types=1);

namespace Structure\Protocols;

interface HandlerInterface {

    /**
     * @param $value
     * @return mixed
     */
    public function filter($value);

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) : bool;

    /**
     * @param string $default
     * @return mixed
     */
    public function default(string $default);

    /**
     * @param string $position
     * @return null
     */
    public function setPosition(string $position);

    /**
     * @return string|null
     */
    public function getPosition(): ?string;
}