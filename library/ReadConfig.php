<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-29
 * Time: 上午6:18
 */

namespace Util;

use Phalcon\Exception;

class ReadConfig {

    private function __construct() {}
    private function __clone() {}

    public static function get($nameString, $diConfig) {
        $names = explode('.', $nameString);
        foreach($names as $name) {
            if(isset($diConfig->{$name})) {
                $diConfig = $diConfig->{$name};
            } else {
                throw new Exception('no config name : '.$name);
            }
        }
        return $diConfig;
    }
}