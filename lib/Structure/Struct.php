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
    const FILTER_NORMAL = 0; # 默认不过滤
    const FILTER_NULL   = 1; # 过滤NULL
    const FILTER_EMPTY  = 2; # 过滤空字符串
    const FILTER_STRICT = 3; # 严格过滤
    const FILTER_KEY    = 4; # 仅输出KEY字段
    # 输出转换
    const OUTPUT_NORMAL = 0; # 默认输出
    const OUTPUT_NULL   = 1; # 空字符串转NULL
    const OUTPUT_EMPTY  = 2; # NULL转空字符串
    const OUTPUT_KEY    = 3; # 仅输出KEY字段

    /**
     * 子类继承重写
     *
     * @var bool 是否将空字符串转换成null
     */
    protected $_empty_to_null = true;

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
    /**
     * @var string 手术刀正则 [注解]
     */
    private $_scalpelPreg = '/@(\w*)(?:\[(\w+)\])?\s+?([^@\n*]+)/';
    /**
     * @var array 验证信息
     */
    protected $_validate = [];



    /**
     * @var string Rule内容键
     * rule content key
     */
    protected $_rck = '';

    /**
     * @var string Rule内容
     * rule content string
     */
    protected $_rcs = '';

    /**
     * @var array Rule内容配置
     * rule content options
     */
    protected $_rco = [];








    protected static $_static_filters = [
        'default'  => 'Structure\Scalpel\Defaults',
        'required' => 'Structure\Scalpel\Required',
        'rule'     => 'Structure\Scalpel\Rule',
        'skip'     => 'Structure\Scalpel\Skip',
        'ghost'    => 'Structure\Scalpel\Ghost',
        'key'      => 'Structure\Scalpel\Key',
        'mapping'  => 'Structure\Scalpel\Mapping',
    ];

    /**
     * @var \ReflectionProperty[]
     */
    private $_fields = null;
    private static $_fields_type = \ReflectionProperty::IS_PUBLIC;

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
     */
    public static function register($name, $class) {
        if (array_key_exists($name, self::$_static_filters)){
            throw new \InvalidArgumentException("@{$name} already", -1);
        }
        if (!is_subclass_of($class, __CLASS__)) {
            throw new \InvalidArgumentException("Invalid Scalpel @{$name} -> {$class}",-2);
        }
        self::$_static_filters[$name] = $class;
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
     * @param null $field
     * @return array|bool|mixed
     */
    final public function getRegister($field = null){
        if($field){
            return isset($this->_register[$field]) ? $this->_register[$field] : false;
        }
        return $this->_register;
    }

    public function getResult(){
        return $this->_scalpel_result;
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
            foreach($registers as $tag => $class){
                if (
                    !array_key_exists($tag, $this->_register) and
                    is_subclass_of($class, ScalpelInterface::class)
                ){
                    $this->_register[$tag] = $class;
                }
            }
        }
    }

    /**
     * Get public reflection properties
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    private function _getFields() {
        if(!$this->_fields instanceof \ReflectionProperty){
            $class = new \ReflectionClass($this);
            $this->_fields = $class->getProperties(self::getFieldsType());
        }
        return $this->_fields;
    }

    /**
     * Add error
     * @param string $field
     * @param string $error
     */
    private function _addError($field, $error) {
        $error = explode(':',$error);
        if(count($error) > 1){
            $this->_errors[$field] = $error[0];
            $this->_codes[$field] = isset($error[1]) ? $error[1] : '500';
        }else{
            $this->_errors[$field] = $error;
            $this->_codes[$field] = '0';
        }
    }

    /**
     * Struct constructor.
     * @param null $data
     * @param string $scene
     */
    public function __construct($data = null, $scene = '') {
        $this->_register($this->register,true);
        $this->_scalpel();                       # 加载手术刀

        $this->setScene($scene);                 # 加载场景
        $this->_setDefault();                    # 加载默认值
        if ($data and is_array($data)) {
            $this->create($data, false); # 创建数据
        }
    }

    /**
     * 手术刀
     * 分析验证规则
     */
    private function _scalpel() {
        $fields = false;
        try{
            $fields = $this->_getFields();
            foreach($fields as $f){
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
                            $format = $class::instance()->handle($rn, $rs, $rc);
                            $this->_scalpel_result[$name][$rn][$rs] = $format->get();
                        }
                    }
                }
            }
            var_dump($this->_scalpel_result);
        }catch (\ReflectionException $e){

        }
//        if($fields){
//            foreach ($fields as $f) {
//                # 信息获取阶段
//                $name = $f->getName(); # 字段名称
//                $comment = $f->getDocComment(); # 字段规则注释
//                # 默认正则结果
//                $matches = null;
//                if ($comment) {
//                    # 正则筛选指令
//                    preg_match_all($this->_scalpelPreg, $comment, $matches);
//                    $this->_validate[$name] = [];
//                    var_dump($comment);var_dump($matches);
//                    # 跳过未有指令的内容
//                    if (!$matches) {
//                        continue;
//                    }
//
//                    for ($i = 0; $i < count($matches[0]); $i++) {
//                        $rn = trim($matches[1][$i]); # 指令名称
//                        $rs = trim($matches[2][$i]); # 指令场景
//                        $rc = trim($matches[3][$i]); # 规则内容
//
//                        switch ($rn) {
//                            # 跳过
//                            case 'skip':
//                                # 鬼魂字段
//                            case 'ghost':
//                                # key字段
//                            case 'key':
//                                # operator字段
//                            case 'operator':
//                                $this->_setValidate($rn,$name,$rs);
//                                break;
//                            case 'mapping':
//                                $this->_setValidate($rn,$name,[
//                                    'content' => $rc,
//                                    'scene' => $rs
//                                ],false);
//                                break;
//                            # 默认值
//                            case 'default':
//                                $rc = explode(':', $rc, 2);
//                                $t = trim($rc[0]); # 类型:int,float,null,string
//                                $v = isset($rc[1]) ? trim($rc[1]) : null; # 值
//
//                                if (!is_null($v)) {
//                                    switch ($t) {
//                                        case 'int':
//                                            $v = intval($v);
//                                            break;
//                                        case 'float':
//                                            $v = floatval($v);
//                                            break;
//                                        case 'null':
//                                            $v = null;
//                                            break;
//                                        case 'func':
//                                            $v = call_user_func($v);
//                                            break;
//                                        case 'method':
//                                            $v = call_user_func_array([$this, $v], []);
//                                            break;
//                                        case 'array':
//                                            $v = json_decode($v, true);
//                                            break;
//                                        case 'bool':
//                                            $v = boolval($v === 'true');
//                                            break;
//                                        default:
//                                            $v = strval($v);
//                                            break;
//                                    }
//
//                                    $this->_setValidate($rn,$name,[
//                                        'content' => $v,
//                                        'scene' => $rs
//                                    ],false);
//                                }
//
//                                break;
//
//                            # 规则
//                            case 'rule':
//                                $rc = explode('|', $rc, 2);
//                                $rc[0] = trim($rc[0]);
//
//                                $rca = explode(',', $rc[0]);
//                                if(count($rca) < 2){
//                                    $rca = explode(':',$rc[0]);
//                                }
//                                $this->_rck = isset($rca[0]) ? trim($rca[0]) : '';
//                                $this->_rcs = isset($rca[1]) ? trim($rca[1]) : '';
//
//                                //                            foreach ($rca as $k => $o){
//                                //                                if($k == 0){
//                                //                                    continue;
//                                //                                }
//                                //                                $o = explode(':', $o, 2);
//                                //                                $this->_rco[$o[0]] = $o[1];
//                                //                            }
//
//                                $rule = [];
//                                switch (true) {
//                                    case $this->_rck === 'func': # 调用函数验证,传入当前字段的值
//                                        $rule['content'] = $this->_rcs;
//                                        break;
//                                    case $this->_rck === 'method': # 调用实例方法验证,传入当字段名称和值
//                                        $rule['content'] = [$this, $this->_rcs];
//                                        break;
//                                    default: # 默认调用验证库
//                                        $rule['content'] = Filter::factory($rc[0]);
//                                }
//
//                                $rule['error'] = isset($rc[1]) ? $rc[1] : "{$name}格式不正确";
//                                $rule['scene'] = $rs;
//
//                                # 设置Validate
//                                $this->_setValidate($rn,$name,$rule,false);
//
//                                break;
//
//                            # 必填字段
//                            case 'required':
//                                $rc = explode('|', $rc);
//
//                                # 设置Validate
//                                $this->_setValidate($rn,$name,[
//                                    'content' => true,
//                                    'scene' => $rs,
//                                    'error' => isset($rc[1]) ? $rc[1] : "{$name}不能为空",
//                                ],false);
//                                break;
//                        }
//                    }
//                }
//            }
//        }
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
     * 设置empty to null
     * @param bool $bool
     * @return $this
     */
    public function emptyToNull(bool $bool){
        $this->_empty_to_null = $bool;
        return $this;
    }

    /**
     * 设置过滤类型
     * @param int $operater OPERATER_开头的常量进行设置
     * @param bool $need    false:不会判断场景 true:会判断特定场景
     * @return $this
     */
    public function setOperator(int $operater,$need = true){
        $this->_operator = $operater;
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
        $this->_empty_to_null = true;
        $this->_operator = self::OPERATOR_CLOSE;
        $this->_operatorKeyNeed = true;
    }

    /**
     * 获得模型实例,此操作未作数据验证
     * @param null|array $data
     * @param string $scene 场景
     * @return static
     */
    public static function factory($data = null, $scene = '') {
        $cls = get_called_class();
        return new $cls($data, $scene);
    }

    /**
     * 设置场景
     * @param $scene
     * @return $this
     */
    public function setScene($scene) {
        $this->_scene = $scene;
        return $this;
    }

    /**
     * 数组输出 [仅输出@key]
     * @param bool $filterNull
     * @param string $scene 场景
     * @return array
     */
    public function outputArrayByKey($filterNull = false,$scene = ''){
        $fields = $this->_getFields();
        $_data = [];

        foreach ($fields as $f) {
            $f = $f->getName();
            if (!$this->_isKeyField($f,$scene)) {
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
     * 数组输出 [新版]
     * @param int $filter
     * @param int $output
     * @param string $scene
     * @return array
     */
    public function outputArray($filter = self::FILTER_NORMAL,$output = self::OUTPUT_NORMAL, $scene = ''){
        $fields = $this->_getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();

            if ($this->_isGhostField($f)) {
                continue; # 排除鬼魂字段
            }

            if (!is_array($this->$f)){
                switch ($filter){
                    case self::FILTER_KEY:
                        if (
                            !$this->_isKeyField($this->$f,$scene)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_STRICT:
                        if (
                            is_null($this->$f) or
                            $this->$f === '' or
                            $this->_isSkipField($f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NULL:
                        if (
                            is_null($this->$f) or
                            $this->_isSkipField($f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_EMPTY:
                        if (
                            $this->$f === '' or
                            $this->_isSkipField($f)
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
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if (($mapping = $this->_isMappingField($f, $scene)) === false){
                continue;
            }
            if ($this->_isGhostField($f)) {
                continue;
            }

            if (!is_array($this->$f)){
                switch ($filter){
                    case self::FILTER_KEY:
                        if (!$this->_isKeyField($this->$f,$scene)) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_STRICT:
                        if (
                            is_null($this->$f) or
                            $this->$f === '' or
                            $this->_isSkipField($f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_NULL:
                        if (
                            is_null($this->$f) or
                            $this->_isSkipField($f)
                        ) {
                            continue 2;
                        }
                        break;
                    case self::FILTER_EMPTY:
                        if (
                            $this->$f === '' or
                            $this->_isSkipField($f)
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

    /**
     * 批量赋值字段
     * @param array $data
     * @param bool $validate
     * @return bool
     */
    public function create(array $data, $validate = true) {
        $fields = $this->_getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if($this->_empty_to_null){
                $_data[$f] = (isset($data[$f]) and $data[$f] !== '') ? $data[$f] : $this->$f;
            }else{
                $_data[$f] = isset($data[$f]) ? $data[$f] : $this->$f;
            }
        }

        # 赋值
        foreach ($_data as $f => $d) {
            $this->$f = $d;
        }

        # 验证
        if ($validate) {
            if (!$this->validate($_data)) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * 清空
     * @param bool $default
     * @return bool
     */
    public function clean($default = false){
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
     * 验证器
     * @param array|null $data
     * @return bool
     */
    public function validate(array $data = null) {
        # 初始化错误记录
        $this->_errors = [];

        if (!$this->_validate) {
            return true;
        }

        if (is_null($data)) {
            $data = $this->toArray();
        }

        $passed = true;

        foreach ($this->_validate as $f => $v) {
            if ($this->_isSkipField($f)) {
                continue; # 排除skip字段
            }

            # ghost字段需要验证

            # 必填值验证
            if (isset($v['required'])) {
                foreach ($v['required'] as $req) {
                    if ($this->_checkScene($req['scene'])) {
                        if (
                            !isset($data[$f]) or
                            $data[$f] === ''
                        ) {
                            $this->_addError($f, $req['error']);
                            $passed = false;
                            continue 2; # 无值不验证
                        }
                    }
                }
            }
            # 规则验证
            if (isset($data[$f]) && $data[$f] !== '' && isset($v['rule'])) {
                foreach ($v['rule'] as $r) {
                    if ($this->_checkScene($r['scene'])) {
                        $validator = $r['content'];

                        # 创建错误(校验过程)
                        $check = true;
                        switch (true){
                            case $this->_rck == 'func':
                                $check = call_user_func($validator, $data[$f]);
                                break;
                            case $this->_rck == 'method':
                                $check = call_user_func($validator, $data[$f], $f, $data);
                                break;
                            case $validator instanceof Filter:
                                $check = $validator->validate($data[$f]);
                                break;
                        }
                        if(!$check){
                            $this->_addError($f, $r['error']);
                            $passed = false;
                        }
                    }
                }
            }
        }

        return $passed;
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
            $this->_isOperatorField($key) and
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
                case self::OPERATER_LOAD_OUTPUT:
                    $valueArr = explode('|',$value);
                    if(count($valueArr) > 1){
                        $res = [];
                        foreach ($valueArr as $value){
                            $match = $this->_operatorPreg($value);
                            if(isset($match['operator'])){
//                                $res["{$key}[{$match['operator']}]"] = $match['column'];
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

                case self::OPERATER_FILTER_OUTPUT:
                    $match = $this->_operatorPreg($value);
                    if(isset($match['column'])){
                        $value = $match['column'];
                    }
                    break;

                case self::OPERATER_CLOASE:
                default:

                    break;
            }
        }
        return [$key,$value];
    }

    /**
     * 设置字段默认值
     */
    private function _setDefault() {
        if ($this->_validate) {
            foreach ($this->_validate as $f => $v) {
                if (isset($v['default'])) {
                    foreach ($v['default'] as $def) {
                        if ($this->_checkScene($def['scene'])) {
                            $this->$f = $def['content'];
                        }
                    }
                }
            }
        }
    }

    /**
     * 检查是适用当前场景
     * @param $scene
     * @return bool
     */
    private function _checkScene($scene) {
        # 如果设置了当前场景,那么当前场景的设置或者未指定场景的指令会被应用
        # 否者,只有未指定场景的指令会被应用
        return $scene == '' or $this->_scene == $scene;
    }

    /**
     * 是否为魔鬼字段
     * @param $field
     * @return bool
     */
    private function _isGhostField($field) {
        if (isset($this->_validate[$field]['ghost'])) {
            foreach ($this->_validate[$field]['ghost'] as $v) {
                if ($this->_checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 是否为跳过的字段
     * @param $field
     * @return bool
     */
    private function _isSkipField($field) {
        if (isset($this->_validate[$field]['skip'])) {
            foreach ($this->_validate[$field]['skip'] as $v) {
                if ($this->_checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 是否为Operator的字段
     * @param $field
     * @return bool
     */
    private function _isOperatorField($field) {
        if(!$this->_operatorKeyNeed){
            return true;
        }
        if (isset($this->_validate[$field]['operator'])) {
            foreach ($this->_validate[$field]['operator'] as $v) {
                if ($this->_checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 是否为key的字段
     * @param $field
     * @param string $scene
     * @return bool
     */
    private function _isKeyField($field,$scene = '') {
        if (isset($this->_validate[$field]['key'])) {
            if($scene){
                foreach ($this->_validate[$field]['key'] as $v) {
                    if ($v['scene'] == '' or $v['scene'] == $scene) {
                        return true;
                    }
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 是否为mapping的字段
     * @param $field
     * @param string $scene
     * @return bool|mixed
     */
    private function _isMappingField($field,$scene = '') {
        if (isset($this->_validate[$field]['mapping'])) {
            if(isset($this->_validate[$field]['mapping'][$scene])){
                return $this->_validate[$field]['mapping'][$scene]['content'];
            }else{
                return $this->_validate[$field]['mapping']['']['content'];
            }
        }
        return false;
    }

    /**
     * 设置验证内容
     * @param $tag
     * @param $name
     * @param $value
     * @param bool $isScene 是否仅传递scene
     */
    private function _setValidate($tag,$name,$value,$isScene = true){
        if (!isset($this->_validate[$name][$tag])) {
            $this->_validate[$name][$tag] = [];
        }
        if($isScene){
            $this->_validate[$name][$tag][$value] = [
                'scene' => $value
            ];
        }else{
            $this->_validate[$name][$tag][$value['scene']] = $value;
        }
//        $this->_validate[$name][$tag][] = $isScene ? [
//            'scene' => $value
//        ] : $value;
    }

}
