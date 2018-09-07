<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure;

class Struct {

    /**
     * @var array 验证信息
     */
    protected $_validate = [];

    /**
     * @var array 错误
     */
    protected $_errors = [];

    /**
     * @var string 当前场景
     */
    protected $_scene = '';

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

    /**
     * Struct constructor.
     * @param null $data
     * @param string $scene
     * @throws \ReflectionException
     */
    public function __construct($data = null, $scene = '') {
        $this->scalpel();                        # 加载手术刀
        $this->setScene($scene);                 # 加载场景
        $this->setDefault();                     # 加载默认值
        if (!is_null($data)) {
            $this->create($data, false); # 创建数据
        }
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
     * 返回数组格式的数据
     * @param bool $filterNull 过滤NULL的开关 默认不过滤
     * @return array
     * @throws \ReflectionException
     */
    public function toArray($filterNull = false) {
        $fields = $this->getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();

            if ($this->isGhostField($f)) {
                continue; # 排除鬼魂字段
            }

            if ($filterNull && !is_array($this->$f)) {
                if ('null' == strtolower($this->$f)) {
                    continue; # 过滤null字段
                }
                if (is_null($this->$f)) {
                    continue; # 过滤null字段
                }

                if ($this->isSkipField($f)) {
                    continue; # 排除skip字段
                }
            }
            $_data[$f] = $this->$f;
        }

        return $_data;
    }

    /**
     * 批量赋值字段
     * @param array $data
     * @param bool $validate
     * @return bool
     * @throws \ReflectionException
     */
    public function create(array $data, $validate = true) {
        $fields = $this->getFields();
        $_data = [];

        foreach ($fields as $f) {
            $f = $f->getName();
            $_data[$f] = isset($data[$f]) ? $data[$f] : $this->$f;
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
     * 验证器
     * @param array|null $data
     * @return bool
     * @throws \ReflectionException
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
            if ($this->isSkipField($f) || $this->isGhostField($f)) {
                continue; # 排除跳过的字段或者魔鬼字段
            }

            # 必填值验证
            if (isset($v['required'])) {
                foreach ($v['required'] as $req) {
                    if ($this->checkScene($req['scene'])) {
                        if (!isset($data[$f]) || $data[$f] === '') {
                            $this->addError($f, $req['error']);
                            $passed = false;
                            continue 2; # 无值不验证
                        }
                    }
                }
            }

            # 规则验证
            if (isset($data[$f]) && $data[$f] !== '' && isset($v['rule'])) {
                foreach ($v['rule'] as $r) {
                    if ($this->checkScene($r['scene'])) {
                        $validator = $r['content'];

                        # 创建错误(校验过程)
                        if (
                            (is_string($validator) and call_user_func($validator, $data[$f]) === false) or # 调用函数
                            (is_array($validator) and call_user_func($validator, $data[$f], $f, $data) === false) or # 调用实例方法
                            ($validator instanceof Filter and $validator->validate($data[$f]) === false) # 调用验证库
                        ) {
                            //todo Filter中validate方法返回数据有误，所以无法正常创建错误信息
                            $this->addError($f, $r['error']);
                            $passed = false;
                        }
                    }
                }
                exit;
            }
        }

        return $passed;
    }

    /**
     * 添加错误
     * @param string $field
     * @param string $error
     */
    public function addError($field, $error) {
        $this->_errors[$field] = $error;
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
     * 获取全部错误
     * @return array|null
     */
    public function getErrors() {
        return $this->_errors ? $this->_errors : null;
    }

    /**
     * 手术刀
     * 分析验证规则
     * @throws \ReflectionException
     */
    protected function scalpel() {
        $fields = $this->getFields();
        foreach ($fields as $f) {
            # 信息获取阶段
            $name = $f->getName(); # 字段名称
            $comment = $f->getDocComment(); # 字段规则注释
            # 默认正则结果
            $matches = null;
            if ($comment) {
                # 正则筛选指令
                preg_match_all('/@(default|rule|required|skip|ghost)(?:\[(\w+)\])?\s+?(.+)/', $comment, $matches);
                $this->_validate[$name] = [];

                # 跳过未有指令的内容
                if (!$matches) {
                    continue;
                }

                for ($i = 0; $i < count($matches[0]); $i++) {
                    $rn = trim($matches[1][$i]); # 指令名称
                    $rs = trim($matches[2][$i]); # 指令场景
                    $rc = trim($matches[3][$i]); # 规则内容

                    switch ($rn) {
                        # 跳过
                        case 'skip':
                            if (!isset($this->_validate[$name]['skip'])) {
                                $this->_validate[$name]['skip'] = [];
                            }
                            $this->_validate[$name]['skip'][] = [
                                'scene' => $rs
                            ];
                            break;
                        # 鬼魂字段
                        case 'ghost':
                            if (!isset($this->_validate[$name]['ghost'])) {
                                $this->_validate[$name]['ghost'] = [];
                            }
                            $this->_validate[$name]['ghost'][] = [
                                'scene' => $rs
                            ];
                            break;
                        # 默认值
                        case 'default':
                            $rc = explode(':', $rc, 2);
                            $t = trim($rc[0]); # 类型:int,float,null,string
                            $v = isset($rc[1]) ? trim($rc[1]) : null; # 值

                            if (!is_null($v)) {
                                switch ($t) {
                                    case 'int':
                                        $v = intval($v);
                                        break;
                                    case 'float':
                                        $v = floatval($v);
                                        break;
                                    case 'null':
                                        $v = null;
                                        break;
                                    case 'func':
                                        $v = call_user_func($v);
                                        break;
                                    case 'method':
                                        $v = call_user_func_array([$this, $v], []);
                                        break;
                                    case 'array':
                                        $v = json_decode($v, true);
                                        break;
                                    case 'bool':
                                        $v = $v === 'true' ? true : false;
                                        break;
                                    default:
                                        $v = strval($v);
                                        break;
                                }

                                if (!isset($this->_validate[$name]['default'])) {
                                    $this->_validate[$name]['default'] = [];
                                }
                                $this->_validate[$name]['default'][] = [
                                    'content' => $v,
                                    'scene' => $rs
                                ];
                            }

                            break;

                        # 规则
                        case 'rule':
                            $rc = explode('|', $rc, 2);
                            $rc[0] = trim($rc[0]);

                            $rca = explode(',', $rc[0]);
                            $this->_rck = trim($rca[0]);
                            $this->_rcs = trim($rca[1]);

//                            foreach ($rca as $k => $o){
//                                if($k == 0){
//                                    continue;
//                                }
//                                $o = explode(':', $o, 2);
//                                $this->_rco[$o[0]] = $o[1];
//                            }

                            $rule = [];
                            switch (true) {
                                case $this->_rck === 'func': # 调用函数验证,传入当前字段的值
                                    $rule['content'] = $this->_rcs;
                                    break;
                                case $this->_rck === 'method': # 调用实例方法验证,传入当字段名称和值
                                    $rule['content'] = [$this, $this->_rcs];
                                    break;
                                default: # 默认调用验证库
                                    $rule['content'] = Filter::factory($rc[0]);
                            }

                            $rule['error'] = isset($rc[1]) ? $rc[1] : "{$name}格式不正确";
                            $rule['scene'] = $rs;

                            # 初始化规则部分
                            if (!isset($this->_validate[$name]['rule'])) {
                                $this->_validate[$name]['rule'] = [];
                            }
                            $this->_validate[$name]['rule'][] = $rule;

                            break;

                        # 必填字段
                        case 'required':
                            $rc = explode('|', $rc);
                            # 初始化规则部分
                            if (!isset($this->_validate[$name]['required'])) {
                                $this->_validate[$name]['required'] = [];
                            }
                            $this->_validate[$name]['required'][] = [
                                'content' => true,
                                'scene' => $rs,
                                'error' => isset($rc[1]) ? $rc[1] : "{$name}不能为空",
                            ];
                            break;
                    }
                }
            }
        }
    }

    /**
     * 反射获取对象属性
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    protected function getFields() {
        $class = new \ReflectionClass($this);
        return $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * 设置字段默认值
     */
    protected function setDefault() {
        if ($this->_validate) {
            foreach ($this->_validate as $f => $v) {
                if (isset($v['default'])) {
                    foreach ($v['default'] as $def) {
                        if ($this->checkScene($def['scene'])) {
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
    protected function checkScene($scene) {
        # 如果设置了当前场景,那么当前场景的设置或者未指定场景的指令会被应用
        # 否者,只有未指定场景的指令会被应用
        return $scene == '' || $this->_scene == $scene;
    }

    /**
     * 是否为魔鬼字段
     * @param $field
     * @return bool
     */
    protected function isGhostField($field) {
        if (isset($this->_validate[$field]['ghost'])) {
            foreach ($this->_validate[$field]['ghost'] as $v) {
                if ($this->checkScene($v['scene'])) {
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
    protected function isSkipField($field) {
        if (isset($this->_validate[$field]['skip'])) {
            foreach ($this->_validate[$field]['skip'] as $v) {
                if ($this->checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

}
