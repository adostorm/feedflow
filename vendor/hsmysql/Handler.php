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
//        static $cacheOpenIndex = array();
//        $key = md5($commandId.$tbname.$index.json_encode($field).json_encode($filter));
//        if(!isset($cacheOpenIndex[$key])) {
//            try {
//                if(null === $index) {
//                    throw new \Exception('error#-97, index is empty');
//                    exit(1);
//                }
//                $hsocket = $this->_initHandlerSocket();
//                $hsocket->openIndex($commandId, $this->config['dbname'], $tbname, $index, $field, $filter);
//                $cacheOpenIndex[$key] = $hsocket;
//            } catch (\HandlerSocketException $e) {
//                echo $e->getMessage();
//            } catch (\Exception $e) {
//                echo $e->getMessage();
//            }
//        }
//        return $cacheOpenIndex[$key];

        $hsocket = $this->_initHandlerSocket();
        $hsocket->openIndex($commandId, $this->config['dbname'], $tbname, $index, $field, $filter);
        return $hsocket;
    }

    public function initCreateIndex($commandId, $tbname, $index=null, $field=null, $options=null) {
        static $cacheCreateIndex = array();
        $key = md5($commandId.$tbname.$index.json_encode($field).json_encode($options));
        if(!isset($cacheCreateIndex[$key])) {
            try {
                if(null === $index) {
                    throw new \Exception('error#-97, index is empty');
                    exit(1);
                }
                $hsocket = $this->_initHandlerSocket();
                $index = $hsocket->createIndex($commandId, $this->config['dbname'], $tbname, $index, $field, $options);
                $cacheCreateIndex[$key] = $index;
            } catch (\HandlerSocketException $e) {
                echo $e->getMessage();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
        return $cacheCreateIndex[$key];
    }

}