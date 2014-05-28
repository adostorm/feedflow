<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-26
 * Time: 下午11:03
 */

namespace HsMysql;

use HsMysql\Op;
use HsMysql\Filter;

class HsModel
{
    private static $_instance = null;

    private $_handlerCaches = array();

    private $_config = array();

    private $_isAssociate = true;

    private $_error = '';

    private $_autoIndex = 1;

    private $_errorInfos = array(
        '121' => "Either Duplicate entry for key '%s' or table was not exists",
        'unauth' => 'Must be auth or Has an error with password',
        'xx' => '[handlersocket] unable to connect 1:1',
        'stmtnum' => 'Either field has not founded or [table | data] was not exists',
        'op'=>'Error with operate character',
    );

    /**
     * @param boolean $isAssociate
     * @return $this
     */
    public function setIsAssociate($isAssociate)
    {
        $this->_isAssociate = $isAssociate;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function init($config)
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        self::$_instance->_setConfigs($config);
        return self::$_instance;
    }

    private function _setConfigs($config)
    {
        $default = array(
            'host' => '',
            'port' => 0,
            'password' => '',
            'dbname' => '',
            'tbname' => '',
            'primary' => 'PRIMARY',
        );
        $this->_config = array_merge($default, $config);
        $this->_autoIndex = $this->_getAutoIndex();
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            throw new \Exception('Has No ' . __CLASS__ . "::" . $name . ' method');
        }
        call_user_func_array(array($this, $name), $arguments);
    }

    private function getHandlerSocketCache()
    {
        $key = $this->_config['host'] . '_' . $this->_config['port'];
        if (!isset($this->_handlerCaches[$key])) {
            try {
                $this->_handlerCaches[$key] =
                    new \HandlerSocket($this->_config['host'], $this->_config['port']);
                $this->_handlerCaches[$key]->auth($this->_config['password']);
            } catch (\HandlerSocketException $e) {
                $this->_error = $e->getMessage();
            }
        }
        return $this->_handlerCaches[$key];
    }

    private function _getAutoIndex()
    {
        static $autoIndex = 1;
        static $autoIndexCaches = array();
        $key = sprintf('%s_%s_%s'
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']);
        if (!isset($autoIndexCaches[$key])) {
            if ($autoIndex == 65535) {
                $autoIndex = 1;
            }
            $autoIndexCaches[$key] = $autoIndex++;
        }
        return $autoIndexCaches[$key];
    }

    public function find($key, $columns, $operate = Op::EQ, $offset = 0, $limit = 1, $filters = array(), $inValues = array())
    {
        $_parser = $this->_parseFilters($filters);
        $in_key = -1;
        if ($inValues) {
            $in_key = 0;
        }
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex($this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $columns
            , $_parser['field']);

        $result = $_handler->executeSingle($this->_autoIndex
            , $operate
            , array($key)
            , $limit
            , $offset
            , null
            , null
            , $_parser['filter']
            , $in_key
            , $inValues);

        $result = $this->_associate($result, $columns);

        if (false === $result) {
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

    private function _countUpdate($key, $data, $mode = '+', $operate = Op::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex($this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , array_keys($data)
            , $_parser['field']);

        $result = $_handler->executeSingle($this->_autoIndex
            , $operate
            , array($key)
            , $limit
            , $offset
            , $mode
            , array_values($data)
            , $_parser['filter']);

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function update($key, $data, $operate = Op::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex($this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , array_keys($data)
            , $_parser['field']);

        $result = $_handler->executeUpdate($this->_autoIndex
            , $operate
            , array($key)
            , array_values($data)
            , $limit
            , $offset
            , $_parser['filter']);

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function delete($key, $operate = Op::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex($this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $_parser['field']
            , $_parser['field']);

        $result = $_handler->executeDelete($this->_autoIndex
            , $operate
            , array($key)
            , $limit
            , $offset
            , $_parser['filter']);

        $result = $this->_associate($result, $_parser['field']);

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function insert($data)
    {
        $_handler = $this->getHandlerSocketCache();

        $_columns = array_keys($data);

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $_columns
        );

        $result = $_handler->executeInsert(
            $this->_autoIndex
            , array_values($data)
        );

        $result = $this->_associate($result, $_columns);

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function multiFind($key, $operate='=', $columns, Filter $filter) {
        $_handler = $this->getHandlerSocketCache();

        $_metas = $filter->getMetas();

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $columns
        );

        $args = array();
        foreach($_metas as $_meta) {
            $_parser = $this->_parseFilters(array($_meta));
            $args[] = array(
                $this->_autoIndex,
                $operate,
                array($key),
                1,
                0,
                null,
                null,
                $_parser['filter']
            );
        }

        $result = $_handler->executeMulti($args);

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    private function _associate($result, $fields) {
        if(!$this->_isAssociate || !is_array($result) || !$result) {
            return $result;
        }
        $rets = array();
        foreach($result as $row) {
            $temp = array();
            foreach ($row as $key => $unit) {
                $temp[$fields[$key]] = $unit;
            }
            $rets[] = $temp;
        }
        return $rets;
    }

    private function _parseFilters($filters)
    {
        $_f1 = null;
        $_f2 = null;
        if ($filters) {
            foreach ($filters as $filter) {
                $_f1[] = strval($filter[0]);
                $_f2[] = array('F', $filter[1], strval($filter[0]), $filter[2]);
            }
        }

        return array(
            'field' => $_f1,
            'filter' => $_f2,
        );
    }

    public function getTraces()
    {
        $_traces['HOST:'] = $this->_config['host'];
        $_traces['PORT:'] = $this->_config['port'];
        $_traces['DATABASE NAME:'] = $this->_config['dbname'];
        $_traces['TABLE NAME:'] = $this->_config['tbname'];
        $_traces['CONNECT ID:'] = $this->_autoIndex;
        $_traces['CONSTRAINT:'] = $this->_config['primary'];
        $_traces['EXECUTE SQL:'] = '';
        $_traces['EXECUTE RESULT:'] = '';
        $_traces['EXECUTE STATUS:'] =
            $this->_error
                ? 'ERROR'
                : 'SUCCESS @(If result equal 0 or empty that Maybe the data was not exists.)';

        if (isset($this->_errorInfos[$this->_error])) {
            $errormsg = $this->_error
                . ' @('
                . sprintf($this->_errorInfos[$this->_error], $this->_config['primary'])
                . ', Please check it and then try again.)';
        } else {
            $errormsg = $this->_error;
        }
        $_traces['ERROR INFO:'] = $errormsg;
        return $_traces;
    }

    public function trace()
    {

        $_traces = $this->getTraces();

        echo PHP_EOL;

        echo str_pad('', 31, '*')
            . 'DEBUG INFORMATION'
            . str_pad('', 32, '*')
            . PHP_EOL;

        if (!$_traces) {
            echo '- PLEASE OPEN DEBUG MODE' . PHP_EOL;
        } else {
            foreach ($_traces as $desc => $info) {
                echo str_pad($desc, 16, ' ', STR_PAD_LEFT) . ' ' . $info . PHP_EOL;
            }
        }

        echo PHP_EOL;

        unset($_traces);
    }
}