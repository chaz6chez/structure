<?php
declare(strict_types=1);

namespace Structure\Handlers;

class URLHandler extends AbstractHandler {

    protected $_defaultOptions = [
        'path' => false,
        'query' => false,
    ];

    public function filter($value) : ?string
    {
        $flags = 0;
        if ($this->getOption('path')) {
            $flags |= FILTER_FLAG_PATH_REQUIRED;
        }
        if ($this->getOption('query')) {
            $flags |= FILTER_FLAG_QUERY_REQUIRED;
        }
        return filter_var($value, FILTER_VALIDATE_URL, $flags);
    }

    public function default(string $default) : ?string
    {
        return $this->validate($default) ? $default : null;
    }

}