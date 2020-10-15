<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Scalpel;

use Structure\Format;

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
     * @return Format
     */
    public function handle($rn, $rs, $rc): Format {
        // todo
        $format = Format::instance();
        $format->_content = $rc;
        $format->_scene = $rs;
        return $format;
    }
}
