<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Scalpel;

use Structure\Filter;
use Structure\Format;
use Structure\Struct;

class Rule implements ScalpelInterface {
    /**
     * @var static
     */
    private static $_instance;

    /**
     * @return ScalpelInterface
     */
    public static function instance(): ScalpelInterface {
        if(!self::$_instance instanceof self){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param $rn
     * @param $rs
     * @param $rc
     * @param Struct $struct
     * @return Format
     */
    public function handle($rn, $rs, $rc, Struct &$struct): Format {
        $rc = explode('|', $rc, 2);
        $rc[0] = trim($rc[0]);

        $rca = explode(',', $rc[0]);
        if(count($rca) < 2) {
            $rca = explode(':', $rc[0]);
        }
        $format = Format::instance();
        $format->_type = isset($rca[0]) ? trim($rca[0]) : '';
        $format->_content = isset($rca[1]) ? trim($rca[1]) : '';
        $format->_scene = $rs;
        $format->_error = isset($rc[1]) ? $rc[1] : "{$struct->getLastField()->getName()} format error";

        switch(true) {
            case $format->_type === 'func':
                $format->_content;
                break;
            case $format->_type === 'method':
                $format->_content = [$struct, $format->_content];
                break;
            default: # 默认调用验证库
                try {
                    $format->_content = Filter::factory($rc[0]);
                }catch(\InvalidArgumentException $exception){
                    $format->_type = 'error';
                }
        }
        return $format;
    }
}
