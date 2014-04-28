<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-27
 * Time: 下午4:03
 */

namespace HSocket;

use HSocket\ModelException;

class Model
{

    const SELECT = 1;
    const UPDATE = 2;
    const INSERT = 3;
    const DELETE = 4;

    private $handler = null;

    private $config = array();

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function connect($commandId, $table, $field, $primary = '')
    {
        try {
            $handler = new \HandlerSocket($this->config['host'], $this->config['port']);
            $isLogin = $handler->auth($this->config['password']);
            if(false == $isLogin) {
                throw new ModelException('db passwd error');
                exit(1);
            }
            $handler->openIndex($commandId, $this->config['dbname'], $table, $primary, $field);
            return $this->handler = $handler;
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        }
    }

    public function getIndex() {
        return $this->index;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->handler, $name), $arguments);
    }
}