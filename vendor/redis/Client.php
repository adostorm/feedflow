<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午4:05
 */

namespace Redisc;

use Phalcon\Exception;

class Client
{

    private $host = '';

    private $port = 0;

    private $isConnected = false;

    private $redis = null;

    public function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
        $this->_init();
    }

    /**
     * 1. check Redis plugin
     * 2. instance redis handler
     * 3. throw exception
     */
    private function _init() {
        if(false == $this->isConnected) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect($this->host, $this->port);
            } catch(Exception $e) {
                echo $e->getMessage();
            }
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