<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-13
 * Time: 下午12:37
 */

namespace Util;

use Phalcon\Logger\Adapter\File as FileAdapter;

final class Logger
{

    private static $instances = array();

    public static function init($di, $filename='')
    {
        $path = ReadConfig::get('application.path', $di);
        $filename = empty($filename) ? date('Y-m-d') . '.log' : $filename;
        $filepath = sprintf('%s/log/%s', $path, $filename);
        if (!isset(self::$instances[$filepath])) {
            self::$instances[$filepath] = new FileAdapter($filepath);
        }
        return self::$instances[$filepath];
    }

}