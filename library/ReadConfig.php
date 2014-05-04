<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-29
 * Time: 上午6:18
 */

namespace Util;

use Phalcon\Exception;

final class ReadConfig {

    private function __construct() {}
    private function __clone() {}

    public static function get($nameString, $di) {
        static $config = null;

        if(null === $config) {
            $config = $di->get('config');
        }

        $value = static::_reduce($nameString, $config);

        return $value;
    }


    private static function _reduce($nameString, $config) {
        $names = explode('.', $nameString);
        foreach($names as $name) {
            if(isset($config->{$name})) {
                $config = $config->{$name};
            } else {
                throw new Exception('no config name : '.$name);
            }
        }
        return $config;
    }

}