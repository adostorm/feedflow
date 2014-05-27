<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-26
 * Time: 上午11:39
 */

namespace HsMysql;


use Phalcon\Exception;

class HandlerClient {

    private static $instance = null;

    private $config = array();

    private $handler = null;

    private $openIndexId = 1;

    private function __construct() {}

    private function __clone() {}

    public function init($host, $port) {

    }

    public function executeSingle() {
        $this->handler->executeSingle();
    }

    public function executeUpdate() {

    }

    public function executeInsert() {

    }

    public function executeDelete() {

    }

    public function __call($name, $arguments) {
        if(!method_exists($this, $name)) {
            throw new Exception('dddd');
        }
    }

}