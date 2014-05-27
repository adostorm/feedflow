<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-26
 * Time: 下午11:03
 */

namespace HsMysql;

use HsMysql\Op;
use HsMysql\Mode;

class HsModel {

    private $_handlerCaches = array();

    private $_config = array();

    private $_error = '';

    private $_errorInfos = array(
        '121'=>"Duplicate entry for key '%s'",
        'unauth'=>'Must be auth or Has an error with password, Please check it and then try again',
        'xx'=>'[handlersocket] unable to connect 1:1',
        'stmtnum'=>'Some field was not found, Please check it and then try again',
    );

    private $_traces = array();

    private $_result = array();

    private static $_instance = null;

    private function __construct() {}

    private function __clone() {}

    public static function init($config) {
        if(null === self::$_instance) {
            self::$_instance = new self;
        }
        self::$_instance->_setConfigs($config);
        return self::$_instance;
    }

    private function _setConfigs($config) {
        $default = array(
            'host'=>'',
            'port'=>0,
            'password'=>'',
            'dbname'=>'',
            'tbname'=>'',
            'primary'=>'PRIMARY',
        );
        $this->_config = array_merge($default, $config);
    }

    public function __call($name, $arguments) {
        if(!method_exists($this, $name)) {
            throw new \Exception('Has No '.__CLASS__."::".$name.' method');
            exit;
        }
        call_user_func_array(array($this, $name), $arguments);
    }

    private function getHandlerSocketCache() {
        $key = $this->_config['host'].'_'.$this->_config['port'];
        if(!isset($this->_handlerCaches[$key])) {
            try {
                $this->_handlerCaches[$key] = new \HandlerSocket($this->_config['host'], $this->_config['port']);
                $this->_handlerCaches[$key]->auth($this->_config['password']);
            } catch(\HandlerSocketException $e) {
                $this->_error = $e->getMessage();
            }

        }
        return $this->_handlerCaches[$key];
    }

    public function find($key, $columns, $operate=Op::EQ, $offset=0, $limit=1, $filters=array(), $inValues=array()) {
        $_parser = $this->_parseFilters($filters);
        $in_key = -1;
        if($inValues) {
            $in_key = 0;
        }
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex(Index::SELECT, $this->_config['dbname'], $this->_config['tbname'], $this->_config['primary'], $columns, $_parser['field']);
        $result = $_handler->executeSingle(Index::SELECT, $operate, array($key), $limit, $offset, null, null, $_parser['filter'], $in_key, $inValues);
        $this->_result = var_export($result, true);
        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function increment($key, $data)
    {
        return $this->_countUpdate($key, $data, '+');
    }

    public function decrement($key, $data)
    {
        return $this->_countUpdate($key, $data, '-');
    }

    private function _countUpdate($key, $data, $mode='+', $operate=Op::EQ, $offset=0, $limit=1, $filters=array()) {
        $_parser = $this->_parseFilters($filters);

        if($this->debug) {
            $chunks1 = array('');
            foreach($filters as $filter) {
                $chunks1[] = implode('', $filter);
            }
            $chunks2 = array('');
            foreach($data as $k=>$v) {
                $chunks2[] = $k.'='.$k.$mode.$v;
            }

            $this->_sql = sprintf('UPDATE `%s` SET %s WHERE 【INDEX%s%s】 %s;'
                , $this->_tbname, trim(implode(' , ',$chunks2),' , '), $operate, $key, implode(' AND ',$chunks1));
        }

        $_handler = $this->getHandlerSocketCache();
        $_handler->openIndex(Index::UPDATE, $this->_config['dbname'], $this->_config['tbname'], $this->_config['primary'], array_keys($data), $_parser['field']);
        $result = $_handler->executeSingle(Index::UPDATE, $operate, array($key), $limit, $offset, $mode, array_values($data), $_parser['filter']);

        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        if($this->debug) {
            $this->_result = var_export($result, true);
        }

        return $result;
    }

    public function update($key, $data, $operate=Op::EQ, $offset=0, $limit=1, $filters=array()) {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();
        $_handler->openIndex(Index::UPDATE, $this->_config['dbname'], $this->_config['tbname'], $this->_config['primary'], array_keys($data), $_parser['field']);
        $result = $_handler->executeUpdate(Index::UPDATE, $operate, array($key), array_values($data), $limit, $offset, $_parser['filter']);
        $this->_result = var_export($result, true);
        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function delete($key, $operate=Op::EQ, $offset=0, $limit=1, $filters=array()) {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();
        $_handler->openIndex(Index::DELETE, $this->_config['dbname'], $this->_config['tbname'], $this->_config['primary'], $_parser['field'], $_parser['field']);
        $result = $_handler->executeDelete(Index::DELETE, $operate, array($key), $limit, $offset, $_parser['filter']);
        $this->_result = var_export($result, true);
        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function insert($data) {
        $_handler = $this->getHandlerSocketCache();
        $_handler->openIndex(Index::INSERT, $this->_config['dbname'], $this->_config['tbname'], $this->_config['primary'], array_keys($data));
        $result = $_handler->executeInsert(Index::INSERT, array_values($data));
        $this->_result = var_export($result, true);
        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    private function _parseFilters($filters) {
        $_f1 = null;
        $_f2 = null;
        if($filters) {
            foreach($filters as $filter) {
                $_f1[] = $filter[0];
                $_f2[] = array('F', $filter[1], $filter[0], $filter[2]);
            }
        }
        return array(
            'field'=>$_f1,
            'filter'=>$_f2,
        );
    }

    public function getTraces() {
        $this->_traces['HOST:'] = $this->_config['host'];
        $this->_traces['PORT:'] = $this->_config['port'];
        $this->_traces['DATABASE NAME:'] = $this->_config['dbname'];
        $this->_traces['TABLE NAME:'] = $this->_config['tbname'];
        $this->_traces['INDEX:']  = $this->_config['primary'];
        $this->_traces['EXECUTE SQL:'] = '';
        $this->_traces['EXECUTE RESULT:'] = $this->_result;
        $this->_traces['EXECUTE STATUS:'] =
            false === $this->_result
                ? 'ERROR'
                : 'SUCCESS @(If result equal 0, Maybe the data was not exists.)';
        $this->_traces['ERROR INFO:'] =
            isset($this->_errorInfos[$this->_error])
                ? $this->_error. ' @(' . sprintf($this->_errorInfos[$this->_error], $this->_config['primary']).'.)'
                : $this->_error;
        return $this->_traces;
    }

    public function trace() {
        echo str_pad('', 31, '-').'DEBUG INFORMATION'.str_pad('', 32, '-').PHP_EOL;
        foreach($this->_traces as $desc=>$trace) {
            echo '- '.$desc.' '.$trace.PHP_EOL;
        }
        echo str_pad('', 80, '-').PHP_EOL;
    }
}