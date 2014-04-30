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

    public function openIndex($commandId, $table, $primary='', $field=array(), $filter=array()) {
        try {
            $handler = new \HandlerSocket($this->config['host'], $this->config['port']);
            $isLogin = $handler->auth($this->config['password']);
            if(false == $isLogin) {
                throw new ModelException('db passwd error');
                exit(1);
            }
            $this->commandId = $commandId;
            if(empty($filter)) {
                $filter = null;
            }
            $handler->openIndex($commandId, $this->config['dbname'], $table, $primary, $field, $filter);
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
            $filter = empty($filter) ? null : array('filter'=>$filter);
            $index = $handler->createIndex($commandId, $this->config['dbname'], $table, $primary, $field,$filter);
            return $this->index = $index;
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        }
    }

}