<?php
declare(strict_types=1);

namespace Structure;

use ReflectionProperty;
use Structure\Exceptions\StructureException;
use Structure\Handlers\AbstractHandler;
use InvalidArgumentException;

abstract class Structure {

    protected $_filters = [];
    protected $_transfers = [];
    protected $_scene = '';

    /**
     * @var null|Structure
     */
    protected $_this;

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
     * 返回一个clone的加载了default至属性的Structure对象
     * @return static
     */
    public function __invoke() : self
    {
        if(!$this->_this instanceof Structure){
            $this->_this = clone $this;
        }
        foreach ($this->_this->_fields as $field){
            $fieldName = $this->_this->_getField($field = $field->getName(), false);
            $this->_this->{$fieldName} = ($this->{$fieldName} === null)
                ? $this->_this->_getValue($field, false)
                : $this->{$fieldName};
        }
        return $this->_this;
    }

    /**
     * @param array $data
     * @param string $scene
     * @return static
     */
    public static function factory(array $data = [], string $scene = '') : Structure
    {
        return new static($data,$scene);
    }

    /**
     * @param int ...$filter
     * @return static
     */
    public function filter(int ...$filter) : Structure
    {
        $this->_filters = array_flip(array_flip($filter));
        return $this;
    }

    /**
     * @param int ...$transfer
     * @return static
     */
    public function transfer(int ...$transfer) : Structure
    {
        $this->_transfers = array_flip(array_flip($transfer));
        return $this;
    }

    /**
     * @param string $scene
     * @return static
     */
    public function scene(string $scene) : Structure
    {
        $this->_scene = $scene;
        return $this;
    }

    /**
     * @param null|array $data
     * @return static
     */
    public function create(?array $data = []): Structure
    {
        $this->clean();
        $this->_raw = $data === null ? [] : $data;
        foreach ($this->_fields as $field) {
            $field = $field->getName();
            $this->{$field} = isset($data[$field])
                ? $data[$field]
                : ($data === null ? $this->{$field} : null);
        }
        return $this;
    }

    /**
     * @param bool $afresh
     * @return bool
     * @throws StructureException
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
                    if(!is_string($error)){
                        throw new StructureException("@required Syntax Error.{[{$this->_scene}]{$fieldName}}" );
                    }
                    if($this->_getValue($fieldName) === null){
                        $this->_addError($fieldName, $error);
                    }
                }
                if([$content, $error] = $this->_getContent($fieldName,STRUCT_TAG_RULE, $this->_scene, true)){
                    if(!is_string($error)){
                        throw new StructureException("@rule Syntax Error.{[{$this->_scene}]{$fieldName}}" );
                    }
                    $value = $this->_getValue($fieldName);
                    if($value === null){
                        continue;
                    }
                    [$mode, $c] = $this->_explode(':', $content, 2);
                    switch ($mode) {
                        case STRUCT_FUNCTION:
                            try {
                                if(!(is_callable($c) ? $c($value) : true)){
                                    $this->_addError($fieldName, $error);
                                }
                            }catch (\Throwable $throwable){
                                throw new StructureException(
                                    "@rule Func Exception.{[{$this->_scene}]{$fieldName}}" ,
                                    $throwable
                                );
                            }
                            break;
                        case STRUCT_METHOD:
                            try {
                                $c = explode(',',$c,2);
                                $method = count($c) > 1 ? [$c[0],$c[1]] : [$this, $c[0]];
                                if(!(is_callable($method) ? $method($value) : true)){
                                    $this->_addError($fieldName, $error);
                                }
                            }catch (\Throwable $throwable){
                                throw new StructureException(
                                    "@rule Method Exception.{[{$this->_scene}]{$fieldName}}" ,
                                    $throwable
                                );
                            }
                            break;
                        default:
                            [$mode, $content] = $this->_explode(',', $content, 2);
                            try{
                                $handler = $this->_handler($mode, Handler::optionsStrToArr($content));
                                if(!$handler->validate($this->_getValue($fieldName))){
                                    $this->_addError($fieldName, $error, $handler->getPosition());
                                }
                            }catch (InvalidArgumentException $exception){
                                throw new StructureException(
                                    "@rule Handler Exception. {[{$this->_scene}]{$fieldName}}" ,
                                    $exception
                                );
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
     * @return static
     */
    public function clean(bool $createRaw = false): Structure
    {
        $raw = $this->getRaw();
        $this->_this = null;
        $this->_filters = [];
        $this->_transfers = [];
        $this->_scene = '';
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
                            if($this->_valueComp($value, null)){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_EMPTY:
                            if($this->_valueComp($value, '')){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_ZERO:
                            if($this->_valueComp($value, 0)){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_KEY:
                            if($this->_getContent($field, STRUCT_TAG_KEY, $this->_scene, true)){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_KEY_REVERSE:
                            if(!$this->_getContent($field, STRUCT_TAG_KEY, $this->_scene, true)){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_OPERATOR:
                            if($this->_getContent($field, STRUCT_TAG_OPERATOR, $this->_scene, true)){
                                continue 3;
                            }
                            break;
                        case STRUCT_FILTER_OPERATOR_REVERSE:
                            if(!$this->_getContent($field, STRUCT_TAG_OPERATOR, $this->_scene, true)){
                                continue 3;
                            }
                            break;
                    }
                }
            }
            $data[$fieldName] = $value;
        }
        if($this->_transfers) {
            $this->_cleanTransferTagCache();
        }
        $this->_filters = $full ? $this->_filters : [];
        $this->_transfers = $full ? $this->_transfers : [];
        return $data;
    }

    /**
     * @param bool $afresh
     * @return bool
     * @throws StructureException
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
        $result = !isset($this->_cache[$this->_scene][$field]) ? $this->{$field} : $this->_cache[$this->_scene][$field];
        if($result === null and !isset($this->_cache[$this->_scene][$field])){
            if([$content, ] = $this->_getContent($field, STRUCT_TAG_DEFAULT, $this->_scene, true)){
                $contents = explode(':', $content, 2);
                if(count($contents) !== 2){
                    throw new StructureException("@default Syntax Error.{[{$this->_scene}]{$field}}");
                }
                [$mode, $content] = $contents;
                switch ($mode){
                    // method:
                    case STRUCT_METHOD:
                        try {
                            $content = explode(',',$content,2);
                            $method = count($content) > 1 ? [$content[0],$content[1]] : [$this, $content[0]];
                            $result = is_callable($method) ? $method() : null;
                        }catch (\Throwable $throwable){
                            throw new StructureException(
                                "@default Method Exception.{[{$this->_scene}]{$field}}" ,
                                $throwable
                            );
                        }
                        break;
                    case STRUCT_FUNCTION:
                        try {
                            $result = function_exists($content) ? $content() : null;
                        }catch (\Throwable $throwable){
                            throw new StructureException(
                                "@default Func Exception.{[{$this->_scene}]{$field}}" ,
                                $throwable
                            );
                        }
                        break;
                    default:
                        try{
                            $result = $this->_handler($mode)->default($content);
                        }catch (InvalidArgumentException $exception){
                            throw new StructureException(
                                "@default Handler Exception.{[{$this->_scene}]{$field}}" ,
                                $exception
                            );
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
                            !([,,$transferResult] = $this->_getTransferTagCache($field,STRUCT_TAG_OPERATOR)) and
                            [$content,] = $this->_getContent($field,STRUCT_TAG_OPERATOR, $this->_scene,true)
                        ){
                            [$mode, $content] = count($contents = explode(':', $content, 2)) !== 2
                                ? [$content,null]
                                : $contents;
                            $key = '';
                            switch ($mode){
                                case STRUCT_METHOD:
                                    try {
                                        $content = explode(',',$content,2);
                                        $method = count($content) > 1 ? [$content[0],$content[1]] : [$this, $content[0]];
                                        $result = is_callable($method) ? $method($result) : null;
                                    }catch (\Throwable $throwable){
                                        throw new StructureException(
                                            "@operator Method Exception.{[{$this->_scene}]{$field}}" ,
                                            $throwable
                                        );
                                    }
                                    break;
                                case STRUCT_FUNCTION:
                                    try {
                                        $result = function_exists($content) ? $content($result) : null;
                                    }catch (\Throwable $throwable){
                                        throw new StructureException(
                                            "@operator Func Exception.{[{$this->_scene}]{$field}}" ,
                                            $throwable
                                        );
                                    }
                                    break;
                                default:
                                    if(is_string($result)){
                                        $match = $this->_operatorPreg($result);
                                        if(isset($match['operator'])) {
                                            $result = count($arr = explode(',',$match['column'])) > 1
                                                ? $arr
                                                : $match['column'];
                                            $key = "[{$match['operator']}]";
                                        }
                                    }
                                    $result = $this->_operatorTypePreg($result);
                                    break;
                            }
                            $this->_setTransferTagCache($field,STRUCT_TAG_OPERATOR,[
                                $key,
                                $field,
                                $result
                            ]);
                        }
                        $result = $transferResult ?? $result;
                        continue 2;
                    case STRUCT_TRANSFER_MAPPING:
                        if(
                            !([,,$transferResult] = $this->_getTransferTagCache($field, STRUCT_TAG_MAPPING)) and
                            $content = $this->_getContent($field,STRUCT_TAG_MAPPING, $this->_scene,true)
                        ){
                            $this->_setTransferTagCache($field,STRUCT_TAG_MAPPING,[
                                trim((string)$content[0]),
                                $field,
                                $result
                            ]);
                        }
                        $result = $transferResult ?? $result;
                        continue 2;
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
        $resField = $field;
        if($transfer){
            foreach ($this->_transfers as $transfer){
                switch ($transfer) {
                    case STRUCT_TRANSFER_OPERATOR:
                        if($this->_getContent($field,STRUCT_TAG_OPERATOR, $this->_scene,true)){
                            $this->_getValue($field);
                            [$column, ,] = $this->_getTransferTagCache($field, STRUCT_TAG_OPERATOR);
                            $resField .= $column;
                        }
                        continue 2;
                    case STRUCT_TRANSFER_MAPPING:
                        if($this->_getContent($field,STRUCT_TAG_MAPPING, $this->_scene,true)) {
                            $this->_getValue($field);
                            [$newField, ,] = $this->_getTransferTagCache($field, STRUCT_TAG_MAPPING);
                            $resField = $resField === $field ? $newField : str_replace($field, $newField, $resField);
                        }
                        continue 2;
                }
            }
        }
        return $resField;
    }

    /**
     * @param string $field
     * @param string $tag
     * @param array $value
     */
    protected function _setTransferTagCache(string $field, string $tag, array $value) : void
    {
        $this->_cache['transfer'][$this->_scene]["_{$tag}_{$field}"] = $value;
    }

    /**
     * @param string $field
     * @param string $tag
     * @return array|null
     */
    protected function _getTransferTagCache(string $field, string $tag) : ?array
    {
        return isset($this->_cache['transfer'][$this->_scene]["_{$tag}_{$field}"])
            ? $this->_cache['transfer'][$this->_scene]["_{$tag}_{$field}"]
            : null;
    }

    /**
     * Clear Transfer Cache
     */
    protected function _cleanTransferTagCache() : void
    {
        $this->_cache['transfer'] = [];
    }

    /**
     * @param string $field
     * @param string $error
     * @param string|null $position
     */
    protected function _addError(string $field, string $error, ?string $position = null){
        [$msg, $code] = count($errors = explode(':', $error, 2)) !== 2 ? [$error, null] : $errors;
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
            if(count($this->_analysis[$field][$tag][$scene]) < 2){
                $this->_analysis[$field][$tag][$scene][] = null;
            }
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
     * @param string $separator
     * @param string $string
     * @param int|null $limit
     * @return string[]
     */
    protected function _explode(string $separator, string $string, ?int $limit = null) :array
    {
        $res = ($string === '') ? [''] : explode($separator, $string, $limit);
        $count = count($res);
        if($limit and $count < $limit){
            $count = $limit - $count;
            do{
                $res[] = '';
                $count--;
            }while($count > 0);
        }
        return $res;
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
     * @param $value
     * @return mixed
     */
    private function _operatorTypePreg($value)
    {
        if(is_array($value)){
            foreach ($value as &$v){
                $v = $this->_operatorTypePreg($v);
            }
        }
        if(is_string($value)){
            preg_match(
                '/(?<column>[\s\S]*(?=\[(?<type>String|Int|Float|Bool)\]$)|[\s\S]*)/',
                $value,
                $match
            );
            $value = $match['column'];
            $type = isset($match['type']) ? $match['type'] : null;
            switch ($type) {
                case 'String':
                    $value = (string)$value;
                    break;
                case 'Int':
                    $value = (int)$value;
                    break;
                case 'Float':
                    $value = (float)$value;
                    break;
                case 'Bool':
                    $value = (bool)$value;
                    break;
                default:
                    if(is_numeric($value)){
                        if(stripos($value,'.')){
                            $value = (float)$value;
                        }else{
                            $value = (int)$value;
                        }
                    }
                    break;
            }
        }

        return $value;
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
                        STRUCT_TAG_MAPPING,
                    ]);
                    preg_match_all(
                        "/@({$tags}|})(?:\[(\w+)\])?\s+?([^@*\n]+)/",
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