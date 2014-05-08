<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-1
 * Time: 下午4:17
 */

namespace HsMysql;

class Handler {

    private $config = array();

    private static $instance = null;

    private function __construct(){}

    private function __clone(){}

    public static function getInstance($config) {
        if(null === self::$instance) {
            self::$instance = new self();
        }
        self::$instance->config = $config;
        return self::$instance;
    }

    private function _initHandlerSocket() {
        static $cacheHandler = array();
        $keyHandler = $this->config['host'].$this->config['port'];
        if(!isset($cacheHandler[$keyHandler])) {
            try {
                $cacheHandler[$keyHandler] = new \HandlerSocket($this->config['host'], $this->config['port']);
                $cacheHandler[$keyHandler]->auth($this->config['password']);
            } catch (\HandlerSocketException $e) {
                echo $e->getMessage();
            }
        }
        return $cacheHandler[$keyHandler];
    }

    public function initOpenIndex($commandId, $tbname, $index=null, $field=null, $filter=null) {
        $hsocket = $this->_initHandlerSocket();
        $hsocket->openIndex($commandId, $this->config['dbname'], $tbname, $index, $field, $filter);
        return $hsocket;
    }

}