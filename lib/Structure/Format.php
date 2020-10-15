<?php
namespace Structure;

final class Format {
    public $_scene   = '';
    public $_error   = '';
    public $_code    = '';
    public $_content = '';

    /**
     * @var \ReflectionProperty[]
     */
    private $_fields;
    /**
     * @var self
     */
    private static $_instance;

    /**
     * Format constructor.
     * @param array $data
     */
    private function __construct(array $data) {
        $this->set($data);
    }

    /**
     * @return \ReflectionProperty|\ReflectionProperty[]
     * @throws \ReflectionException
     */
    private function _getField(){
        if(!$this->_fields instanceof \ReflectionProperty){
            $class = new \ReflectionClass($this);
            $this->_fields = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        }
        return $this->_fields;
    }

    /**
     * Instance
     * @param array $data
     * @return Format
     */
    public static function instance(array $data = []){
        if(self::$_instance instanceof self){
            self::$_instance->clean();
            return self::$_instance;
        }
        return self::$_instance = new self($data);
    }

    /**
     * clean
     */
    public function clean(){
        $this->_scene   = '';
        $this->_code    = '';
        $this->_content = '';
        $this->_error   = '';
    }

    /**
     * Set data
     * @param array $data
     */
    public function set(array $data){
        if($data){
            $this->_scene   = isset($data['_scene']) ? $data['_scene'] : '';
            $this->_code    = isset($data['_code']) ? $data['_code'] : '';
            $this->_content = isset($data['_content']) ? $data['_content'] : '';
            $this->_error   = isset($data['_error']) ? $data['_error'] : '';
        }
    }

    /**
     * Get data
     * @return array
     */
    public function get(){
        $data = [];
        try {
            $fields = $this->_getField();
            foreach($fields as $f){
                $data[$f->getName()] = $f->getValue($this);
            }
            return $data;
        }catch(\ReflectionException $exception){
            return $data;
        }
    }
}
