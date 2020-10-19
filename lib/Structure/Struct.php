<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure;

use Structure\Scalpel\ScalpelInterface;

class Struct {
    # 数值过滤
    const OPERATOR_CLOSE         = 0; # 默认关闭
    const OPERATOR_LOAD_OUTPUT   = 1; # 装载输出
    const OPERATOR_FILTER_OUTPUT = 2; # 过滤输出
    # 参数过滤 []
    const FILTER_NORMAL = 10; # 默认不过滤
    const FILTER_NULL   = 11; # 过滤NULL
    const FILTER_EMPTY  = 12; # 过滤空字符串
    const FILTER_STRICT = 13; # 严格过滤
    const FILTER_KEY    = 14; # 仅对@key字段过滤
    # 输出转换
    const OUTPUT_NORMAL  = 110; # 默认输出
    const OUTPUT_NULL    = 111; # 空字符串转NULL
    const OUTPUT_EMPTY   = 112; # NULL转空字符串
    const OUTPUT_KEY     = 113; # 输出@key字段
    const OUTPUT_MAPPING = 114; # 输出@mapping字段


    /**
     * @var int 特殊值的处理
     */
    protected $_operator        = self::OPERATOR_CLOSE;
    /**
     * @var bool 默认对带 @operator标签的进行转化
     */
    protected $_operatorKeyNeed = true;
    /**
     * @var string 操作者正则 [用于特殊赋值的过滤和操作] [column仅做了包含性判断]
     */
    private $_operatorPreg = '/(?<column>[\s\S]*(?=\[(?<operator>\+|\-|\*|\/|\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\]$)|[\s\S]*)/';


    protected static $_static_filters = [
        'required' => 'Structure\Scalpel\Required',
        'default'  => 'Structure\Scalpel\Defaults',
        'rule'     => 'Structure\Scalpel\Rule',
        'skip'     => 'Structure\Scalpel\Skip',
        'ghost'    => 'Structure\Scalpel\Ghost',
        'key'      => 'Structure\Scalpel\Key',
        'operator' => 'Structure\Scalpel\Operator',
        'mapping'  => 'Structure\Scalpel\Mapping',
    ];

    /**
     * @var \ReflectionProperty[]
     */
    private $_fields = null;
    /**
     * @var \ReflectionProperty
     */
    private $_field = null;
    private static $_fields_type = \ReflectionProperty::IS_PUBLIC;
    /**
     * @var static
     */
    private static $_instance;

    private $_scalpel_preg = '/@(\w*)(?:\[(\w+)\])?\s+?([^@\n*]+)/';
    private $_scalpel_result = [];

    private $_errors = [];
    private $_codes = [];
    private $_scene = '';
    private $_register = [];

    protected $register = [];

    /**
     * Global register tag
     * @param $name
     * @param $class
     * @param $validate
     */
    public static function register($name, $class, $validate) {
        if (array_key_exists($name, self::$_static_filters)){
            throw new \InvalidArgumentException("@{$name} already", -1);
        }
        if (!is_subclass_of($class, __CLASS__)) {
            throw new \InvalidArgumentException("Invalid Scalpel @{$name} -> {$class}",-2);
        }
        self::$_static_filters[$name] = [$class,$validate];
    }

    /**
     * Set fields type
     *  This is a global setting and will affect all [Struct]
     *
     * @param int $reflectionPropertyConst
     */
    public static function setFieldsType(int $reflectionPropertyConst){
        self::$_fields_type = $reflectionPropertyConst;
    }

    /**
     * Get fields type
     * @return int
     */
    public static function getFieldsType(){
        return self::$_fields_type;
    }

    /**
     * @param string $field
     * @return ScalpelInterface[]|ScalpelInterface
     */
    final public function getRegister(string $field = ''){
        if($field){
            return isset($this->_register[$field]) ? $this->_register[$field] : [];
        }
        return $this->_register;
    }

    public function getResult(){
        return $this->_scalpel_result;
    }

    final public function getLastField(){
        return $this->_field;
    }

    /**
     * Global register tag
     * @param array $registers
     * @param bool $init
     */
    private function _register(array $registers, $init = false){
        if($init){
            $this->_register = self::$_static_filters;
        }
        if($registers){
            foreach($registers as $tag => $register){
                if (
                    !array_key_exists($tag, $this->_register) and
                    is_subclass_of($register, ScalpelInterface::class)
                ){
                    $this->_register[$tag] = $register;
                }
            }
        }
    }

    /**
     * Get public reflection properties
     * @return \ReflectionProperty[]
     */
    private function _getFields() {
        try {
            if(!$this->_fields instanceof \ReflectionProperty){
                $class = new \ReflectionClass($this);
                $this->_fields = $class->getProperties(self::getFieldsType());
            }
        }catch(\ReflectionException $exception){
            # When new \ReflectionClass, if the class does not exist,\ReflectionException will be thrown,
            # but this will not happen here
        }

        return $this->_fields;
    }

    /**
     * Add error
     * @param string $field
     * @param string $error
     * @param string $code
     */
    private function _addError(string $field, string $error, $code = '-500') {
        if($error){
            $this->_errors[$field] = $error;
            if($code){
                $this->_codes[$field]  = $code;
            }
        }
    }

    /**
     * Struct constructor.
     * @param array $data
     * @param string $scene
     */
    private function __construct(array $data = [], $scene = '') {
        $this->_register($this->register,true);
        $this->_scalpel();
        $this->setScene($scene);
        $this->_setDefault();
        $this->create($data, false,true);
    }

    /**
     * Scalpel procedure
     */
    private function _scalpel() {
        $fields = $this->_getFields();
        foreach($fields as $f){
            $this->_field = $f;
            $name = $f->getName(); # 字段名称
            $comment = $f->getDocComment(); # 字段规则注释
            if ($comment) {
                preg_match_all($this->_scalpel_preg, $comment, $matches);
                $this->_scalpel_result[$name] = [];
                if (!$matches) continue;
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $rn = trim($matches[1][$i]); # 指令名称
                    $rs = trim($matches[2][$i]); # 指令场景
                    $rc = trim($matches[3][$i]); # 规则内容
                    if(!$class = $this->getRegister($rn)){
                        continue;
                    }
                    if(
                        is_subclass_of($class,ScalpelInterface::class) and
                        class_exists($class)
                    ){
                        $format = $class::instance()->handle($rn, $rs, $rc, $this);
                        $this->_scalpel_result[$name][$rn][$rs] = $format->get();
                    }
                }
            }
        }
    }

    /**
     * Set the default value of the attribute
     */
    private function _setDefault() {
        if ($this->_scalpel_result) {
            foreach ($this->_scalpel_result as $f => $v) {
                if (isset($v['default'])) {
                    foreach ($v['default'] as $def) {
                        $format = Format::instance()->set($def);
                        if ($this->_checkScene($format->_scene)) {
                            $this->$f = $format->_content;
                        }
                    }
                }
            }
        }
    }

    /**
     * Is tag
     * @param string $tag
     * @param string $field
     * @param bool $cover true or false, the default non-scene tag will cover the scene tag
     * @return bool Non-scene tags > scene tags , Non-scene tags are effective in any scene
     */
    private function _isTagField(string $tag, string $field, bool $cover = true) {
        if (isset($this->_scalpel_result[$field][$tag])) {
            if(isset($this->_scalpel_result[$field][$tag]['']) and $cover){
                return true;
            }
            if(isset($this->_scalpel_result[$field][$tag][$this->_scene])){
                return true;
            }
        }
        return false;
    }

    /**
     * Use Tag`s Validate
     * @param string $tag
     * @param string $field
     * @return bool
     */
    private function _useTagValidate(string $tag, string $field) : bool {
        if(
            $class = $this->getRegister($tag) and
            is_subclass_of($class,ScalpelInterface::class) and
            class_exists($class)
        ){
            return $class::instance()->validate($field, $this);
        }
        return true;
    }

    /**
     * Factory
     * @param array $data
     * @param string $scene
     * @return mixed
     */
    public static function factory(array $data = [],string $scene = '') {
        $cls = get_called_class();
        return new $cls($data, $scene);
    }

    /**
     * Set Struct scene
     * @param $scene
     * @return $this
     */
    public function setScene($scene) {
        $this->_scene = $scene;
        return $this;
    }

    /**
     * Create data
     *
     * @param array $data
     * @param bool $validate
     * @param bool $restrict
     *      true or false, the default is True,the empty string in $data
     * will be converted to NULL of the corresponding attribute of Struct
     * @return bool
     */
    public function create(array $data, bool $validate = true, bool $restrict = true) {
        if($data){
            $fields = $this->_getFields();
            $_data = [];
            foreach ($fields as $f) {
                $f = $f->getName();
                if($restrict){
                    $this->$f = $_data[$f] = (isset($data[$f]) and $data[$f] !== '') ? $data[$f] : $this->$f;
                }else{
                    $this->$f = $_data[$f] = isset($data[$f]) ? $data[$f] : $this->$f;
                }
            }
            if ($validate) {
                if (!$this->validate()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * clean
     * @param bool $default
     * @return bool
     */
    public function clean(bool $default = false){
        $fields = $this->_getFields();
        foreach ($fields as $f) {
            $f = $f->getName();
            $this->$f = null;
        }
        if($default){
            $this->_setDefault();
        }
        return true;
    }

    /**
     * @param string $tag
     * @param string $field
     * @param bool true or false, the default non-scene tag will cover the scene tag
     * @return Format Non-scene tags > scene tags , Non-scene tags are effective in any scene
     */
    final public function getTagFormat(string $tag, string $field, bool $cover = true){

        if (isset($this->_scalpel_result[$field][$tag])) {
            if(isset($this->_scalpel_result[$field][$tag]['']) and $cover){

                return Format::instance()->set($this->_scalpel_result[$field][$tag]['']);
            }
            if(isset($this->_scalpel_result[$field][$tag][$this->_scene])){
                return Format::instance()->set($this->_scalpel_result[$field][$tag][$this->_scene]);
            }
        }
        return Format::instance();
    }

    /**
     * validate base
     * @return bool
     */
    public function validate() {
        $this->_errors = [];
        $this->_codes  = [];
        if ($this->_scalpel_result) {
            foreach ($this->_scalpel_result as $field => $content) {
                # @skip
                if($this->_isTagField('skip', $field)){
                    continue;
                }
                # @required
                if(
                    $this->_isTagField('required',$field) and
                    !$this->_useTagValidate('required',$field)
                ){
                    $support = $this->getTagFormat('required',$field);
                    $this->_addError($field, $support->_error, $support->_code);
                    continue;
                }
                # @rule
                if(
                    $this->_isTagField('rule',$field) and
                    !$this->_useTagValidate('rule',$field)
                ){
                    $support = $this->getTagFormat('rule',$field);
                    $this->_addError($field, $support->_error, $support->_code);
                    continue;
                }
            }
        }
        return true;
    }

    /**
     * 数组输出 [新版]
     * @param int $filter
     * @param int $output
     * @param string $scene
     * @return array
     */
    final public function outputArray($filter = self::FILTER_NORMAL, $output = self::OUTPUT_NORMAL, $scene = ''){
        $fields = $this->_getFields();
        if ($scene){
            $this->setScene($scene);
        }
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if($this->_isTagField('ghost', $f)){
                continue;
            }
            if (!is_array($this->$f)){
                switch ($filter){
                    case self::FILTER_KEY:
                        if (!$this->_isTagField('key', $f)) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_STRICT:
                        if (
                            is_null($this->$f) or
                            $this->$f === '' or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NULL:
                        if (
                            is_null($this->$f) or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_EMPTY:
                        if (
                            $this->$f === '' or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NORMAL:
                    default:
                        break;
                }
            }
            $value = '';
            switch ($output){
                case self::OUTPUT_NULL:
                    $value = $this->$f === '' ? null : $this->$f;
                    break;
                case self::OUTPUT_EMPTY:
                    $value = is_null($this->$f) ? '' : $this->$f;
                    break;
                case self::OUTPUT_KEY:
                    if(!$this->_isTagField('key',$f)){
                        continue 2;
                    }
                    break;
                case self::OUTPUT_MAPPING:
                    if(!$this->_isTagField('mapping',$f)){
                        continue 2;
                    }
                    break;
                case self::OUTPUT_NORMAL:
                default:
                    $value = $this->$f;
                    break;
            }
            $res = $this->_parsingOperator($f,$value);
            if(Filter::factory('assoc')->validate($res)){
                foreach($res as $k => $v){
                    if($k === 'key'){
                        continue;
                    }
                    $_data[$res['key'].$k] = $v;
                }
            }else{
                $_data[$res[0]] = $res[1];
            }
        }
        $this->cleanSet();
        return $_data;
    }

    /**
     * @param $var
     * @param Struct $struct
     * @return false|int|string
     */
    public function getVariableName(&$var,Struct $struct){
        $tmp = $var;
        $var = 'tmp_value_'.mt_rand();
        $name = array_search($var, $struct->outputArray());
        $var = $tmp;
        return $name;
    }

    /**
     * 确认错误
     * @param null $filed
     * @return bool|mixed
     */
    public function hasError($filed = null) {
        if (is_null($filed)) {
            return count($this->_errors) > 0;
        } else {
            return $this->_errors[$filed];
        }
    }

    /**
     * 获取第一条错误
     * @return string|null
     */
    public function getError() {
        return $this->_errors ? array_values($this->_errors)[0] : null;
    }

    /**
     * 获取第一条错误码
     * @return string|null
     */
    public function getCode() {
        return $this->_codes ? array_values($this->_codes)[0] : null;
    }

    /**
     * 获取全部错误
     * @return array
     */
    public function getErrors() {
        return $this->_errors ? $this->_errors : [];
    }

    /**
     * 获取全部错误码
     * @return array
     */
    public function getCodes() {
        return $this->_codes ? $this->_codes : [];
    }

    /**
     * 过滤正则辅助
     * @param $value
     * @return mixed
     */
    private function _operatorPreg($value){
        preg_match($this->_operatorPreg, $value, $match);
        return $match;
    }

    /**
     * 分析operator
     * @param $key
     * @param $value
     * @return array
     *
     * key   = array[0]
     * value = array[1]
     */
    private function _parsingOperator($key,$value){
        if(
            $this->_isTagField('operator', $key) and
            $value and
            is_string($value)
        ){
            switch ($this->_operator){
                /**
                 * 说明
                 *
                 *  1.多重数据可以以 | 分割
                 *      例： 123[>]|456[<] 会转化成两个键和值
                 *  2.<> >< 两种方式需要使用 , 间隔数据
                 *      例：123,456[><] 会转化成 [123,456]
                 *
                 */
                case self::OPERATOR_LOAD_OUTPUT:
                    $valueArr = explode('|',$value);
                    if(count($valueArr) > 1){
                        $res = [];
                        foreach ($valueArr as $value){
                            $match = $this->_operatorPreg($value);
                            if(isset($match['operator'])){
                                $res["[{$match['operator']}]"] = $match['column'];
                            }
                            $res['key'] = $key;
                        }
                        return $res;
                    }else{
                        $match = $this->_operatorPreg($value);
                        if(isset($match['operator'])){
                            $key = "{$key}[{$match['operator']}]";
                            $value = $match['column'];
                            if(
                                $match['operator'] == '<>' or
                                $match['operator'] == '><'
                            ){
                                $value = explode(',',$match['column']);
                            }
                        }
                    }
                    break;
                case self::OPERATOR_FILTER_OUTPUT:
                    $match = $this->_operatorPreg($value);
                    if(isset($match['column'])){
                        $value = $match['column'];
                    }
                    break;
                case self::OPERATOR_CLOSE:
                default:
                    break;
            }
        }
        return [$key,$value];
    }

    /**
     * 检查是适用当前场景
     * @param $scene
     * @return bool
     */
    private function _checkScene($scene) {
        return $scene == '' or $this->_scene == $scene;
    }


    /**
     * 设置过滤类型
     * @param int $operator OPERATOR_开头的常量进行设置
     * @param bool $need    false:不会判断场景 true:会判断特定场景
     * @return $this
     */
    public function setOperator(int $operator,$need = true){
        $this->_operator = $operator;
        $this->_operatorKeyNeed = $need;
        return $this;
    }

    /**
     * @return int
     */
    public function getOperator(){
        return $this->_operator;
    }

    /**
     * 重置设置项
     */
    public function cleanSet(){
        $this->_operator = self::OPERATOR_CLOSE;
        $this->_operatorKeyNeed = true;
    }

    /**
     * 数组输出 [仅输出@key]
     * @param bool $filterNull
     * @param string $scene 场景
     * @return array
     */
    public function outputArrayByKey($filterNull = false, $scene = ''){
        $fields = $this->_getFields();
        if($scene){
            $this->setScene($scene);
        }
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if (!$this->_isTagField('key',$f)) {
                continue; # 排除非key字段
            }
            if(!is_array($this->$f)){
                if ($filterNull){
                    if (is_null($this->$f)) {
                        continue; # 过滤null字段
                    }
                }
            }
            $res = $this->_parsingOperator($f,$this->$f);
            if(Filter::factory('assoc')->validate($res)){
                foreach($res as $k => $v){
                    if($k === 'key'){
                        continue;
                    }
                    $_data[$res['key'].$k] = $v;
                }
            }else{
                $_data[$res[0]] = $res[1];
            }
        }
        $this->cleanSet();
        return $_data;
    }

    /**
     * 数组映射输出
     * @param int $filter
     * @param int $output
     * @param string $scene
     * @return array
     */
    public function outputArrayUseMapping($filter = self::FILTER_NORMAL,$output = self::OUTPUT_NORMAL, $scene = ''){
        $fields = $this->_getFields();
        if($scene){
            $this->setScene($scene);
        }
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if (($mapping = $this->_isTagField('mapping', $f)) === false){
                continue;
            }
            if ($this->_isTagField('ghost', $f)) {
                continue;
            }

            if (!is_array($this->$f)){
                switch ($filter){
                    case self::FILTER_KEY:
                        if (!$this->_isTagField('key',$this->$f)) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_STRICT:
                        if (
                            is_null($this->$f) or
                            $this->$f === '' or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NULL:
                        if (
                            is_null($this->$f) or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_EMPTY:
                        if (
                            $this->$f === '' or
                            $this->_isTagField('skip', $f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NORMAL:
                    default:
                        break;
                }
            }

            switch ($output){
                case self::OUTPUT_NULL:
                    $value = $this->$f === '' ? null : $this->$f;
                    break;
                case self::OUTPUT_EMPTY:
                    $value = is_null($this->$f) ? '' : $this->$f;
                    break;
                case self::OUTPUT_NORMAL:
                default:
                    $value = $this->$f;
                    break;
            }
            $res = $this->_parsingOperator($f,$value);
            if(Filter::factory('assoc')->validate($res)){
                foreach($res as $k => $v){
                    if($k === 'key'){
                        continue;
                    }
                    if(!$mapping){
                        $_data[] = $v;
                    }else{
                        $_data[$mapping.$k] = $v;
                    }
                }
            }else{
                if(!$mapping){
                    $_data[$res[0]] = $res[1];
                }else{
                    $_data[$mapping] = $res[1];
                }
            }
        }
        $this->cleanSet();
        return $_data;
    }



}
