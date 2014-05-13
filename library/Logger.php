<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-13
 * Time: 下午12:37
 */

namespace Util;

use Phalcon\Logger\Adapter\File as FileAdapter;

final class Logger {

    private static $instances = array();

    public static function init($filepath='') {
        $filepath = empty($filepath) ? date('Y-m-d').'.log' : $filepath;
        $filename = sprintf('../log/%s', $filepath);
        if(!isset(self::$instances[$filename])) {
            self::$instances[$filename] = new FileAdapter($filename);
        }
        return self::$instances[$filename];
    }

}