<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Scalpel;

use Structure\Format;
use Structure\Struct;

class Defaults implements ScalpelInterface {
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
        $rc = explode(':', $rc, 2);
        $t = trim($rc[0]); # 类型:int,float,null,string
        $v = isset($rc[1]) ? trim($rc[1]) : null; # 值

        if(!is_null($v)) {
            switch($t) {
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
                    $v = function_exists($v) ? call_user_func($v) : '';
                    break;
                case 'method':
                    $v = is_callable([$struct, $v]) ? call_user_func_array([$struct, $v], []) : '';
                    break;
                case 'array':
                    $v = json_decode($v, true);
                    break;
                case 'bool':
                    $v = boolval($v === 'true');
                    break;
                default:
                    $v = strval($v);
                    break;
            }
        }
        $format = Format::instance();
        $format->_content = $v;
        $format->_scene   = $rs;
        return $format;
    }

    public function validate(string $field, Struct &$struct): bool {
        return true;
    }
}
