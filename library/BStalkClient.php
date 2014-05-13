<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-3
 * Time: 上午3:22
 */

namespace Util;

use Phalcon\Queue\Beanstalk;

final class BStalkClient {

    private static $cacheConfigs = null;

    private function __construct() {}
    private function __clone(){}

    public static function getInstance($di, $queueName='link_queue0') {
        if(!isset(self::$cacheConfigs[$queueName])) {
            $config = array(
                'host'=>\Util\ReadConfig::get(sprintf('beanstalk.%s.host', $queueName), $di),
                'port'=>\Util\ReadConfig::get(sprintf('beanstalk.%s.port', $queueName), $di),
            );
            self::$cacheConfigs[$queueName] = $config;
            unset($config);
        }
        return new Beanstalk(self::$cacheConfigs[$queueName]);
    }

}