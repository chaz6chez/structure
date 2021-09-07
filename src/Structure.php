<?php
declare(strict_types=1);

namespace Structure;

use ReflectionProperty;
use Structure\Handlers\AbstractHandler;
use InvalidArgumentException;

abstract class Structure {

    protected $_filters = [];
    protected $_scene;

    /**
     * @var ReflectionProperty[]
     */
    protected $_fields;
    /**
     * @var array 注释分析结果集 [map]
     *
     *  {field_name} => [
     *    {tag_name} => [
     *      {scene_name} => [
     *        {content},{error}
     *      ]
     *    ]
     *  ]
     */
    protected $_analysis = [];

    /**
     * @var array
     */
    protected $_cache = [];
    /**
     * @var array
     */
    protected $_raw = [];
    /**
     * @var Error[] 错误信息
     */
    protected $_errors = [];

    /**
     * @var AbstractHandler[]
     */
    protected static $_handler = [];

    /**
     * Structure constructor.
     * @param array $data
     * @param string $scene
     */
    public function __construct(array $data = [], string $scene = '')
    {
        $this->_fields()
            ->_analysis()
            ->create($data)
            ->scene($scene);
    }

    /**
     * @param int ...$filter
     * @return $this
     */
    public function filter(int ...$filter) : Structure
    {
        $this->_filters = array_flip(array_flip($filter));
        return $this;
    }

    /**
     * @param string $scene
     * @return $this
     */
    public function scene(string $scene) : Structure
    {
        $this->_scene = $scene;
        return $this;
    }

    /**
     * @param array $data
     * @return static
     */
    public function create(array $data = []): Structure
    {
        if($data){
            $this->_raw = $data;
            foreach ($this->_fields as $field) {
                $field = $field->getName();
                $this->{$field} = isset($data[$field]) ? $data[$field] : $this->{$field};
            }
        }
        return $this;
    }

    /**
     * @param bool $afresh
     * @return bool
     */
    public function validate(bool $afresh = false) : bool
    {
        $this->_errors = !$afresh ? $this->_errors : [];
        if(!$this->_errors){
            foreach ($this->_analysis as $fieldName => $value){
                if($this->_getContent($fieldName,STRUCT_TAG_SKIP, $this->_scene, true)){
                    continue;
                }
                if([, $error] = $this->_getContent($fieldName,STRUCT_TAG_REQUIRED, $this->_scene, true)){
                    if($this->_getValue($fieldName) === null){
                        $this->_addError($fieldName, $error);
                    }
                }
                if([$content, $error] = $this->_getContent($fieldName,STRUCT_TAG_RULE, $this->_scene, true)){
                    [$mode, $c] = explode(':', $content, 2);
                    switch ($mode) {
                        case 'func':
                            if(!(is_callable($c) ? $c() : true)){
                                $this->_addError($fieldName, $error);
                            }
                            break;
                        case 'method':
                            $c = explode(',',$c,2);
                            $method = count($c) > 1 ? [$c[0],$c[1]] : [$this, $c[0]];
                            if(!(is_callable($method) ? $method() : true)){
                                $this->_addError($fieldName, $error);
                            }
                            break;
                        default:
                            [$mode, $content] = explode(',', $content, 2);
                            try{
                                $handler = $this->_handler($mode, Handler::optionsStrToArr($content));
                                if(!$handler->validate($this->_getValue($fieldName))){
                                    $this->_addError($fieldName, $error, $handler->getPosition());
                                }
                            }catch (InvalidArgumentException $exception){

                            }
                            break;
                    }
                }
            }
        }
        return $this->hasError();
    }

    /**
     * @param bool $createRaw
     * @return $this
     */
    public function clean(bool $createRaw = false): Structure
    {
        $raw = $this->getRaw();
        $this->_filters = [];
        $this->_scene = null;
        $this->_raw = [];
        $this->_errors = [];
        $this->_cache = [];
        self::$_handler = [];
        if($createRaw){
            return $this->create($raw);
        }
        return $this;
    }

    /**
     * @param bool $full
     * @return array
     */
    public function output(bool $full = false) : array
    {
        $data = [];
        foreach ($this->_fields as $field){
            $field = $field->getName();
            $value = $this->_getValue($field);
            if(!$full){
                foreach ($this->_filters as $filter) {
                    switch ($filter) {
                        case STRUCT_FILTER_NULL:
                            if(!$this->_valueComp($value, null)){
                                continue 2;
                            }
                            break;
                        case STRUCT_FILTER_EMPTY:
                            if(!$this->_valueComp($value, '')){
                                continue 2;
                            }
                            break;
                        case STRUCT_FILTER_ZERO:
                            if($this->_valueComp($value, 0)){
                                continue 2;
                            }
                            break;
                        case STRUCT_FILTER_KEY:
                            if($this->_getContent($field, STRUCT_TAG_KEY, $this->_scene, true)){
                                continue 2;
                            }
                            break;
                        case STRUCT_FILTER_KEY_REVERSE:
                            if(!$this->_getContent($field, STRUCT_TAG_KEY, $this->_scene, true)){
                                continue 2;
                            }
                            break;
                    }
                }
            }
            $data[$field] = $value;
        }
        return $data;
    }

    /**
     * @return bool
     */
    public function hasError() : bool
    {
        return !empty($this->_errors);
    }

    /**
     * @return Error
     */
    public function getError() : Error
    {
        return $this->hasError() ? array_values($this->getErrors())[0] : new Error(null,null);
    }

    /**
     * @return Error[]
     */
    public function getErrors() : array
    {
        return $this->_errors;
    }

    /**
     * @return array
     */
    public function getRaw(): array
    {
        return $this->_raw;
    }

    /**
     * @param string $name
     * @param array|null $options
     * @return AbstractHandler
     * @throws InvalidArgumentException
     */
    protected function _handler(string $name, ?array $options = null) : AbstractHandler
    {
        if(!isset(self::$_handler[$name])){
            self::$_handler[$name] = Handler::factory($name);
        }
        if($options){
            self::$_handler[$name]->setOptions($options);
        }
        return self::$_handler[$name];
    }

    /**
     * @param string $field
     * @return mixed|string|object|array|int|float
     */
    protected function _getValue(string $field)
    {
        $result = $this->{$field};
        if($result === null and isset($this->_cache[$field])){
            if([$content, ] = $this->_getContent($field, STRUCT_TAG_DEFAULT, $this->_scene, true)){
                [$mode, $content] = explode(':', $content, 2);
                switch ($mode){
                    // method:
                    case 'method':
                        $content = explode(',',$content,2);
                        $method = count($content) > 1 ? [$content[0],$content[1]] : [$this, $content[0]];
                        $result = is_callable($method) ? $method() : null;
                        break;
                    case 'func':
                        $result = function_exists($content) ? $content() : null;
                        break;
                    default:
                        try{
                            $result = $this->_handler($mode)->default($content);
                        }catch (InvalidArgumentException $exception){

                        }
                        break;
                }
            }
            $this->_cache[$field] = $result;
        }
        return $result;
    }

    /**
     * @param string $field
     * @param string $error
     * @param string|null $position
     */
    protected function _addError(string $field, string $error, ?string $position = null){
        [$msg, $code] = explode(':', $error, 2);
        $this->_errors[] = new Error($field, $msg, $code, $position);
    }

    /**
     * @param string $field
     * @param string $tag
     * @param string $scene
     * @param bool $default
     * @return array|null
     */
    protected function _getContent(string $field, string $tag, string $scene, bool $default = false) : ?array
    {
        if(isset($this->_analysis[$field][$tag][$scene])){
            return $this->_analysis[$field][$tag][$scene];
        }
        if($scene !== '' and $default){
            return $this->_getContent($field, $tag,'');
        }
        return null;
    }


    /**
     * @param mixed $actual
     * @param mixed $expected
     * @return bool
     */
    protected function _valueComp($actual, $expected) : bool
    {
        return $actual === $expected;
    }

    /**
     * @return static
     */
    protected function _fields(): Structure
    {
        if(!$this->_fields){
            $class = new \ReflectionClass($this);
            $this->_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);
        }
        return $this;
    }

    /**
     * Annotation analysis
     * @return static
     */
    protected function _analysis(): Structure
    {
        if(!$this->_analysis){
            foreach ($this->_fields as $field){
                $name = $field->getName();
                $comment = $field->getDocComment();
                if($comment and $name){
                    $tags = implode('|', [
                        STRUCT_TAG_DEFAULT,
                        STRUCT_TAG_RULE,
                        STRUCT_TAG_REQUIRED,
                        STRUCT_TAG_SKIP,
                        STRUCT_TAG_GHOST,
                        STRUCT_TAG_KEY,
                        STRUCT_TAG_OPERATOR,
                        STRUCT_TAG_MAPPING
                    ]);
                    preg_match_all(
                        "/@({$tags}})(?:\[(\w+)\])?\s+?([^@*\n]+)/",
                        $comment,
                        $matches
                    );
                    if (!$matches or !is_array($matches)) {
                        continue;
                    }
                    [, $tags, $scenes, $contents] = $matches;
                    foreach ($tags as $key => $tag){
                        $this->_analysis[$name][$tag][$scenes[$key]]
                            = explode('|',trim($contents[$key]),2);
                    }
                }
            }
        }
        return $this;
    }
}