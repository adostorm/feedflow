<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午4:05
 */

namespace Util;

use Phalcon\Exception;
use Util\ReadConfig;

final class RedisClient
{

    private $host = '';

    private $port = 0;

    private $password = '';

    private $isConnected = false;

    private static $redis = null;

    private function __construct() {}
    private function __clone(){}

    public static function getInstance($di) {
        if(null === self::$redis) {
            self::$redis = new self;
            self::$redis->host = ReadConfig::get('redis_connect.host', $di);
            self::$redis->port = ReadConfig::get('redis_connect.port', $di);
            self::$redis->password = ReadConfig::get('redis_connect.password', $di);
            self::$redis->_init();
        }
        return self::$redis;
    }

    /**
     * 1. check Redis plugin
     * 2. instance redis handler
     * 3. throw exception
     */
    private function _init() {
        try {
            $this->redis = new \Redis();
            $this->redis->connect($this->host, $this->port);
            if($this->password) {
                $status = $this->auth($this->password);
                if(false === $status) {
                    throw new Exception('redis auth error : authenticate fail');
                    exit(1);
                }
            }
            $this->isConnected = true;
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function __call($name, $arguments) {
        if(!method_exists($this->redis, $name)) {
            throw new Exception(sprintf('class "%s" does not have a method "%s" in %s on line %s',
                'Redis', $name, __FILE__, __LINE__));
        }
        return call_user_func_array(array($this->redis, $name), $arguments);
    }

}