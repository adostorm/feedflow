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

    private $index = null;

    private $commandId = 0;

    private $config = array();

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function openIndex($commandId, $table, $primary='', $field=array()) {
        try {
            $handler = new \HandlerSocket($this->config['host'], $this->config['port']);
            $isLogin = $handler->auth($this->config['password']);
            if(false == $isLogin) {
                throw new ModelException('db passwd error');
                exit(1);
            }
            $this->commandId = $commandId;
            $handler->openIndex($commandId, $this->config['dbname'], $table, $primary, $field);
            return $this->handler = $handler;
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        }
    }

    public function createIndex($commandId, $table, $primary='', $field=array(), $filter=array()) {
        try {
            $handler = new \HandlerSocket($this->config['host'], $this->config['port']);
            $isLogin = $handler->auth($this->config['password']);
            if(false == $isLogin) {
                throw new ModelException('db passwd error');
                exit(1);
            }
            $this->commandId = $commandId;
            $filter = empty($filter) ? $filter : array('filter'=>$filter);
            $index = $handler->createIndex($commandId, $this->config['dbname'], $table, $primary, $field,$filter);
            return $this->index = $index;
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $keys
     * @param string $op
     * @return mixed
     *
     *  array(1, '>=', array('K1')),
        array(1, '>=', array('K1'), 3),
        array(1, '>=', array('K1'), 1, 0, null, null, array('F', '>', 0, 'F1')),
        array(1, '=', array('K1'), 1, 0, 'U', array('KEY1', 'VAL1'))
     */
    public function find($keys, $op='=') {
        if(is_array($keys)) {
            $query = array();
            foreach($keys as $key) {
                $query[] = array($this->commandId, $op, array($key));
            }
            return $this->handler->executeMulti($query);
        } else {
            return $this->handler->executeSingle($this->commandId, $op, array($keys));
        }
    }

    public function findByFilter() {

    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->handler, $name), $arguments);
    }
}