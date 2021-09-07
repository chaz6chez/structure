<?php
declare(strict_types=1);

namespace Structure\Handlers;

class IPHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'ipv4' => true,
        'ipv6' => true,
        'private' => true,
        'reserved' => true,
    ];

    public function filter($value) : ?string
    {
        $flags = 0;
        if ($this->getOption('ipv4') === true) {
            $flags |= FILTER_FLAG_IPV4;
        }
        if ($this->getOption('ipv6') === true) {
            $flags |= FILTER_FLAG_IPV6;
        }
        if ($this->getOption('private') !== true) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if ($this->getOption('reserved') !== true) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }
        return filter_var($value, FILTER_VALIDATE_IP, $flags);
    }

    public function default(string $default) : ?string
    {
        if($this->filter($default)){
            return (string)$default;
        }
        return null;
    }
}