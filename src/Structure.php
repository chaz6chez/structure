<?php
declare(strict_types=1);

namespace Structure;

use ReflectionProperty;
use Structure\Handlers\AbstractHandler;
use InvalidArgumentException;

abstract class Structure {

    protected $_filters = [];
    protected $_transfers = [];
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
     * @param int ...$transfer
     * @return $this
     */
    public function transfer(int ...$transfer) : Structure
    {
        $this->_transfers = array_flip(array_flip($transfer));
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
        return !empty($this->_errors);
    }

    /**
     * @param bool $createRaw
     * @return $this
     */
    public function clean(bool $createRaw = false): Structure
    {
        $raw = $this->getRaw();
        $this->_filters = [];
        $this->_transfers = [];
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
            $fieldName = $this->_getField($field);
            $value = $this->_getValue($field);
            if(!$full){
                if($this->_getContent($field, STRUCT_TAG_GHOST, $this->_scene, true)){
                    continue;
                }
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
                        case STRUCT_FILTER_OPERATOR:
                            if($this->_getContent($field, STRUCT_TAG_OPERATOR, $this->_scene, true)){
                                continue 2;
                            }
                            break;
                        case STRUCT_FILTER_OPERATOR_REVERSE:
                            if(!$this->_getContent($field, STRUCT_TAG_OPERATOR, $this->_scene, true)){
                                continue 2;
                            }
                            break;
                    }
                }
                $this->_filters = [];
                $this->_transfers = [];
            }
            $data[$fieldName] = $value;
        }
        return $data;
    }

    /**
     * @param bool $afresh
     * @return bool
     */
    public function hasError(bool $afresh = false) : bool
    {
        return $this->validate($afresh);
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
     * @param bool $transfer
     * @return mixed|string|object|array|int|float
     */
    protected function _getValue(string $field, bool $transfer = true)
    {
        $result = $this->{$field};
        if($result === null and isset($this->_cache[$this->_scene][$field])){
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
            $this->_cache[$this->_scene][$field] = $result;
        }
        if($transfer){
            foreach ($this->_transfers as $transfer){
                switch ($transfer) {
                    case STRUCT_TRANSFER_OPERATOR:
                        if(
                            !$this->_getTagCache($field, STRUCT_TAG_OPERATOR) and
                            $this->_getContent($field,STRUCT_TAG_OPERATOR, $this->_scene,true) and
                            is_string($result)
                        ){
                            $match = $this->_operatorPreg($result);
                            $this->_setTagCache($field,STRUCT_TAG_OPERATOR,isset($match['operator'])
                                ? [
                                    "{$field}[{$match['operator']}]",
                                    $field,
                                    $result = count($arr = explode(',',$match['column'])) > 1
                                            ? $arr
                                            : $match['column']
                                ]
                                : [
                                    $field,
                                    $field,
                                    $result
                                ]);
                        }
                        break;
                    case STRUCT_TRANSFER_MAPPING:
                        if(
                            !$this->_getTagCache($field, STRUCT_TAG_MAPPING) and
                            $content = $this->_getContent($field,STRUCT_TAG_MAPPING, $this->_scene,true)
                        ){
                            $this->_setTagCache($field,STRUCT_TAG_MAPPING,[
                                trim((string)$content),
                                $field,
                                $result
                            ]);
                        }
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $field
     * @param bool $transfer
     * @return string
     */
    protected function _getField(string $field, bool $transfer = true) : string
    {
        if($transfer){
            foreach ($this->_transfers as $transfer){
                switch ($transfer) {
                    case STRUCT_TRANSFER_OPERATOR:
                        if($this->_getContent($field,STRUCT_TAG_OPERATOR, $this->_scene,true)){
                            $this->_getValue($field);
                            [$field,,] = $this->_getTagCache($field, STRUCT_TAG_OPERATOR);
                        }
                        break;
                    case STRUCT_TRANSFER_MAPPING:
                        if($this->_getContent($field,STRUCT_TAG_MAPPING, $this->_scene,true)){
                            $this->_getValue($field);
                            [$field,,] = $this->_getTagCache($field, STRUCT_TAG_MAPPING);
                        }
                        break;
                }
            }
        }
        return $field;
    }

    /**
     * @param string $field
     * @param string $tag
     * @param array $value
     */
    protected function _setTagCache(string $field, string $tag, array $value) : void
    {
        $this->_cache[$this->_scene]["_{$tag}_{$field}"] = $value;
    }

    /**
     * @param string $field
     * @param string $tag
     * @return array|null
     */
    protected function _getTagCache(string $field, string $tag) : ?array
    {
        return isset($this->_cache[$this->_scene]["_{$tag}_{$field}"])
            ? $this->_cache[$this->_scene]["_{$tag}_{$field}"]
            : null;
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
     * @param $value
     * @return mixed
     */
    private function _operatorPreg($value)
    {
        preg_match(
            '/(?<column>[\s\S]*(?=\[(?<operator>\+|\-|\*|\/|\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\]$)|[\s\S]*)/',
            $value,
            $match
        );
        return $match;
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