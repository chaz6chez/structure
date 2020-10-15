<?php
namespace Structure\Scalpel;

use Structure\Format;

interface ScalpelInterface {

    /**
     *
     * @return static
     */
    public static function instance() : self;

    /**
     *
     * @param $rn
     * @param $rs
     * @param $rc
     * @return Format
     */
    public function handle($rn, $rs, $rc) : Format ;
}