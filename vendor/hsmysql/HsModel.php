<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-26
 * Time: 下午11:03
 */

namespace HsMysql;

final class E {
    const SELECT = 1;
    const INSERT = 2;
    const UPDATE = 3;
    const DELETE = 4;
}

final class O {
    const EQ = '=';
    const GT = '>';
    const GTEQ = '>=';
    const LT = '<';
    const LTEQ = '<=';
}


class HsModel {

    private $_handlerCaches = array();

    private $_host = '';

    private $_port = '';

    private $_dbname = '';

    private $_tbname = '';

    private $_primary = '';

    private $_traces = '';

    private $_error = '';

    private $_result = array();

    private $_sql = '';

    public $debug = true;

    private static $_instance = null;

    private function __construct() {}

    private function __clone() {}

    public static function init($host, $port, $dbname, $tbname, $primary='PRIMARY') {
        if(null === self::$_instance) {
            self::$_instance = new self;
        }
        self::$_instance->setVariables($host, $port, $dbname, $tbname, $primary);
        return self::$_instance;
    }

    private function setVariables($host, $port, $dbname, $tbname, $primary) {
        $this->_host = $host;
        $this->_port = $port;
        $this->_dbname = $dbname;
        $this->_tbname = $tbname;
        $this->_primary = $primary;
    }

    public function __call($name, $arguments) {
        if(!method_exists($this, $name)) {
            throw new \Exception('Has No '.__CLASS__."::".$name.' method');
            exit;
        }
        call_user_func_array(array($this, $name), $arguments);
    }

    private function getHandlerSocketCache($host, $port) {
        $key = $host.'_'.$port;
        if(!isset($this->_handlerCaches[$key])) {
            $this->_handlerCaches[$key] = new \HandlerSocket($host, $port);
        }
        return $this->_handlerCaches[$key];
    }

    public function find($key, $columns, $operate=O::EQ, $offset=0, $limit=1, $filters=array(), $inValues=array()) {
        $_parser = $this->_parseFilters($filters);
        $in_key = -1;
        if($inValues) {
            $in_key = 0;
        }

        if($this->debug) {
            $chunks = array('');
            foreach($filters as $filter) {
                $chunks[] = implode('', $filter);
            }

            $this->_sql = sprintf('SELECT %s FROM `%s` WHERE 【INDEX%s%s】 %s %s;'
                ,implode(',', $columns) , $this->_tbname, $operate, $key, implode(' AND ',$chunks), $in_key===0 ? ' AND【INDEX】 IN ('.implode(',', $inValues).')':'');
        }


        $_handler = $this->getHandlerSocketCache($this->_host, $this->_port);
        $_handler->openIndex(E::SELECT, $this->_dbname, $this->_tbname, $this->_primary, $columns, $_parser['field']);
        $result = $_handler->executeSingle(E::SELECT, $operate, array($key), $limit, $offset, null, null, $_parser['filter'], $in_key, $inValues);

        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        if($this->debug) {
            $this->_result = var_export($result, true);
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

    private function _countUpdate($key, $data, $mode='+', $operate=O::EQ, $offset=0, $limit=1, $filters=array()) {
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

        $_handler = $this->getHandlerSocketCache($this->_host, $this->_port);
        $_handler->openIndex(E::UPDATE, $this->_dbname, $this->_tbname, $this->_primary, array_keys($data), $_parser['field']);
        $result = $_handler->executeSingle(E::UPDATE, $operate, array($key), $limit, $offset, $mode, array_values($data), $_parser['filter']);

        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        if($this->debug) {
            $this->_result = var_export($result, true);
        }

        return $result;
    }

    public function update($key, $data, $operate=O::EQ, $offset=0, $limit=1, $filters=array()) {
        $_parser = $this->_parseFilters($filters);

        if($this->debug) {
            $chunks1 = array('');
            foreach($filters as $filter) {
                $chunks1[] = implode('', $filter);
            }
            $chunks2 = array('');
            foreach($data as $k=>$v) {
                $chunks2[] = $k.'='.$v;
            }

            $this->_sql = sprintf('UPDATE `%s` SET %s WHERE 【INDEX%s%s】 %s;'
                , $this->_tbname, trim(implode(' , ',$chunks2),' , '), $operate, $key, implode(' AND ',$chunks1));
        }

        $_handler = $this->getHandlerSocketCache($this->_host, $this->_port);
        $_handler->openIndex(E::UPDATE, $this->_dbname, $this->_tbname, $this->_primary, array_keys($data), $_parser['field']);
        $result = $_handler->executeUpdate(E::UPDATE, $operate, array($key), array_values($data), $limit, $offset, $_parser['filter']);

        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        if($this->debug) {
            $this->_result = var_export($result, true);
        }

        return $result;
    }

    public function delete($key, $operate=O::EQ, $offset=0, $limit=1, $filters=array()) {
        $_parser = $this->_parseFilters($filters);

        if($this->debug) {
            $chunks = array('');
            foreach($filters as $filter) {
                $chunks[] = implode('', $filter);
            }
            $this->_sql = sprintf('DELETE FROM `%s` WHERE 【INDEX%s%s】 %s;'
                , $this->_tbname, $operate, $key, implode(' AND ',$chunks));
        }

        $_handler = $this->getHandlerSocketCache($this->_host, $this->_port);
        $_handler->openIndex(E::DELETE, $this->_dbname, $this->_tbname, $this->_primary, $_parser['field'], $_parser['field']);
        $result = $_handler->executeDelete(E::DELETE, $operate, array($key), $limit, $offset, $_parser['filter']);

        if(false === $result) {
            $this->_error = $_handler->getError();
        }
        if($this->debug) {
            $this->_result = var_export($result, true);
        }

        return $result;
    }

    public function insert($data) {
        $_handler = $this->getHandlerSocketCache($this->_host, $this->_port);
        $_handler->openIndex(E::INSERT, $this->_dbname, $this->_tbname, $this->_primary, array_keys($data));
        $result = $_handler->executeInsert(E::INSERT, array_values($data));

        if(false === $result) {
            $this->_error = $_handler->getError();
        }

        if($this->debug) {
            $this->_sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s);'
                , $this->_tbname, implode(',',array_keys($data)), implode(',',array_values($data)));
            $this->_result = var_export($result, true);
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

    public function trace() {
        $this->_traces['HOST:'] = $this->_host;
        $this->_traces['PORT:'] = $this->_port;
        $this->_traces['DATABASE NAME:'] = $this->_dbname;
        $this->_traces['TABLE NAME:'] = $this->_tbname;
        $this->_traces['INDEX:']  = $this->_primary;
        $this->_traces['EXECUTE SQL:'] = $this->_sql;
        $this->_traces['EXECUTE RESULT:'] = $this->_result;
        $this->_traces['ERROR INFO:'] = $this->_error;

        echo str_pad('', 31, '-').'DEBUG INFORMATION'.str_pad('', 32, '-').PHP_EOL;
        foreach($this->_traces as $desc=>$trace) {
            echo '- '.$desc.' '.$trace.PHP_EOL;
        }
        echo str_pad('', 80, '-').PHP_EOL;
    }
}