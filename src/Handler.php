<?php
declare(strict_types=1);

namespace Structure;

use Structure\Handlers\AbstractHandler;
use Structure\Handlers\ArrayHandler;
use Structure\Handlers\BoolHandler;
use Structure\Handlers\FloatHandler;
use Structure\Handlers\IntHandler;
use Structure\Handlers\IPHandler;
use Structure\Handlers\MapHandler;
use Structure\Handlers\ObjectHandler;
use Structure\Handlers\RegexHandler;
use Structure\Handlers\StringHandler;
use Structure\Handlers\URLHandler;

class Handler {

    /**
     * @var string[]
     */
    protected static $_filters = [
        'array'  => ArrayHandler::class,
        'map'    => MapHandler::class,
        'bool'   => BoolHandler::class,
        'float'  => FloatHandler::class,
        'int'    => IntHandler::class,
        'ip'     => IPHandler::class,
        'object' => ObjectHandler::class,
        'string' => StringHandler::class,
        'url'    => URLHandler::class,
        'regex'  => RegexHandler::class,
    ];

    /**
     * @param string $name
     * @param string|null $options
     * @return AbstractHandler
     */
    public static function factory(string $name, ?string $options = null) : AbstractHandler
    {
        if (!isset(self::$_filters[$name])) {
            throw new \InvalidArgumentException("Handler Not Found [{$name}]" ,-1);
        }
        return new self::$_filters[$name](static::optionsStrToArr($options));
    }

    /**
     * @param string $name
     * @param AbstractHandler $handler
     */
    public static function register(string $name, AbstractHandler $handler) {
        if (isset(self::$_filters[$name = strtolower($name)])) {
            throw new \InvalidArgumentException("Handler Already Exists [{$name}]",-2);
        }
        self::$_filters[$name] = $handler;
    }

    /**
     * 分析器 min:4,max:10,values:array
     * @param string|null $options
     * @return array|null
     */
    public static function optionsStrToArr(?string $options) : ?array
    {
        if($options){
            $parts = explode(',', $options);
            $options = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) {
                    continue;
                }
                [$k, $v] = explode(':', $part, 2);
                $options[$k] = $v;
            }
            return $options;
        }
        return null;
    }
}
