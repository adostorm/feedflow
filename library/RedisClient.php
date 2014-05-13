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

    private $redis = null;

    private $logger = null;

    private static $instance = null;

    private function __construct() {}
    private function __clone(){}

    public static function getInstance($di) {
        if(null === self::$instance) {
            $_instance = new self;
            $_instance->host = ReadConfig::get('redis_connect.host', $di);
            $_instance->port = ReadConfig::get('redis_connect.port', $di);
            $_instance->password = ReadConfig::get('redis_connect.password', $di);
            $_instance->_init();
            $_instance->logger = \Util\Logger::init();
            self::$instance = $_instance;
            unset($_instance);
        }
        return self::$instance;
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
                    throw new Exception('redis auth error : authenticate fail'.PHP_EOL);
                    exit(1);
                }
            }
            $this->isConnected = true;
        } catch(Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }


    public function __call($name, $arguments) {
        if(method_exists($this->redis, $name)) {
            return call_user_func_array(array($this->redis, $name), $arguments);
        } else {
            $this->logger->log("reids method \"{$name}\" not found", \Phalcon\Logger::ERROR);
            throw new Exception("reids method \"{$name}\" not found".PHP_EOL);
            exit;
        }
    }
}