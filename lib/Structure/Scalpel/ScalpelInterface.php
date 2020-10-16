<?php
namespace Structure\Scalpel;

use Structure\Format;
use Structure\Struct;

interface ScalpelInterface {

    /**
     *
     * @return static
     */
    public static function instance() : self;

    /**
     * @param $rn
     * @param $rs
     * @param $rc
     * @param Struct $struct
     * @return Format
     */
    public function handle($rn, $rs, $rc, Struct &$struct) : Format ;

    /**
     * @param $tagInfo
     * @return Format
     */
    public function validate($tagInfo) : Format;
}