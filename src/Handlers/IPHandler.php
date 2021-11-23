<?php
declare(strict_types=1);

namespace Structure\Handlers;

class IPHandler extends StringHandler {

    protected $_defaultOptions = [
        'ipv4' => true,
        'ipv6' => true,
        'private' => false,
        'reserved' => false,
    ];

    public function filter($value) : ?string
    {
        $flags = 0;
        if ($this->getOption('ipv4') and $this->getOption('ipv4') !== 'false') {
            $flags |= FILTER_FLAG_IPV4;
        }
        if ($this->getOption('ipv6') and $this->getOption('ipv6') !== 'false') {
            $flags |= FILTER_FLAG_IPV6;
        }
        if ($this->getOption('private') and $this->getOption('private') === 'true') {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if ($this->getOption('reserved') and $this->getOption('reserved') === 'true') {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }
        return filter_var($value, FILTER_VALIDATE_IP, $flags) ? (string)$value : null;
    }

    public function default(string $default) : ?string
    {
        return $this->validate($default) ? $default : null;
    }
}