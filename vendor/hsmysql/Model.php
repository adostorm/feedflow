<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-1
 * Time: 上午1:39
 */

namespace HsMysql;

use Util\ReadConfig;

class Model
{

    const READ_PORT = 1;

    const WRITE_PORT = 2;

    const SELECT = 1;

    const INSERT = 2;

    const UPDATE = 3;

    const DELETE = 4;

    public $index = '';

    public $tbname = '';

    public $dbname = '';

    public $multi = false;

    public $filter = null;

    private $fields = '';

    private $limit = 1;

    private $offset = 0;

    private $indexFilter = null;

    private $whereFilter = null;

    private $di = null;

    private $isAssociate = true;

    public $insertId = 0;

    /**
     * @param boolean $isAssociate
     */
    public function setIsAssociate($isAssociate)
    {
        $this->isAssociate = $isAssociate;
    }

    public function __construct($di, $link='')
    {
        $this->di = $di;
        $this->_parseName($link);
    }

    private function _parseName($link)
    {
        if (stripos($link, '.') > 0) {
            list($dbname, $tbname) = explode('.', $link);
            $this->dbname = $dbname;
            $this->tbname = $tbname;
        }
        if (!$this->dbname) {
            throw new \Exception('error#-99 : no dbname');
        } else if (!$this->tbname) {
            throw new \Exception('error#-98 : no tbname');
        }
    }

    private function _parseConfig($readOrWrite = self::WRITE_PORT)
    {
        $config = array();
        if (is_object($this->di)) {
            $link = sprintf('link_%s', $this->dbname);
            $config['host'] = ReadConfig::get("{$link}.host", $this->di);
            $config['password'] = ReadConfig::get("{$link}.password", $this->di);
            $config['dbname'] = ReadConfig::get("{$link}.dbname", $this->di);
            if ($readOrWrite == self::READ_PORT) {
                $config['port'] = ReadConfig::get("{$link}.hs_read_port", $this->di);
            } else if ($readOrWrite == self::WRITE_PORT) {
                $config['port'] = ReadConfig::get("{$link}.hs_write_port", $this->di);
            }
        }
        return $config;
    }

    public function field($field)
    {
        $this->fields = $this->_parseStringTpArray($field);
        return $this;
    }

    public function index($index)
    {
        $this->index = $index;
        return $this;
    }

    public function filter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    public function associate($bool = true)
    {
        $this->isAssociate = $bool;
    }

    public function find($key = '', $op = '=')
    {
        $this->_parseFilter();
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::READ_PORT));
        $handlersocket = $handler->initOpenIndex(self::SELECT, $this->tbname, $this->index, $this->fields, $this->indexFilter);
        $result = $handlersocket->executeSingle(self::SELECT, $op, array($key), $this->limit, $this->offset, null, null, $this->whereFilter);
        $result = $this->_parseData($result);
        return $result;
    }

    public function update($key, $data, $op = '=')
    {
        $this->_parseFilter();
        $_fields = array();
        $_values = array();
        foreach ($data as $_field => $_value) {
            $_fields[] = $_field;
            $_values[] = $_value;
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        $handlersocket = $handler->initOpenIndex(self::UPDATE, $this->tbname, $this->index, $_fields, $this->indexFilter);
        $result = $handlersocket->executeUpdate(self::UPDATE, $op, array($key), $_values, $this->limit, $this->offset, $this->whereFilter);
        return $result;
    }

    public function insert($data)
    {
        $_fields = array();
        $_values = array();
        foreach ($data as $_field => $_value) {
            $_fields[] = $_field;
            $_values[] = $_value;
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        $handlersocket = $handler->initOpenIndex(self::INSERT, $this->tbname, $this->index, $_fields);
        $result = $handlersocket->executeInsert(self::INSERT, $_values);
        return $result;
    }

    public function delete($key, $op = '=')
    {
        $this->_parseFilter();
        $field = array();
        if (null !== $this->indexFilter) {
            $field = $this->indexFilter;
        }
        if (!$field) {
            throw new \Exception('no field , please set any field like this: $model->field([...]);');
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        $handlersocket = $handler->initOpenIndex(self::DELETE, $this->tbname, $this->index, $field, $this->indexFilter);
        $result = $handlersocket->executeDelete(self::DELETE, $op, array($key), $this->limit, $this->offset, $this->whereFilter);
        return $result;
    }

    public function multi()
    {

    }

    private function _parseData($data)
    {
        $newData = array();
        if ($this->isAssociate && $data && is_array($data)) {
            foreach ($data as $row) {
                $temp = array();
                foreach ($row as $key => $unit) {
                    $temp[$this->fields[$key]] = $unit;
                }
                $newData[] = $temp;
            }
        } else {
            $newData = $data;
        }
        if ($this->limit == 1 && isset($newData[0])) {
            $newData = $newData[0];
        }
        return $newData;
    }

    private function _parseFilter()
    {
        if ($this->filter) {
            $tempIndexFilter = array();
            foreach ($this->filter as $filter) {
                $tempIndexFilter[] = $filter[0];
            }
            $tempIndexFilter = array_unique($tempIndexFilter);
            $this->indexFilter = $tempIndexFilter;
            $flip = array_flip($tempIndexFilter);
            $tempWhereFilter = array();
            foreach ($this->filter as $filter) {
                $tempWhereFilter = array('F', $filter[1], $flip[$filter[0]], $filter[2]);
            }
            $this->whereFilter = array($tempWhereFilter);
            unset($tempIndexFilter, $tempWhereFilter, $flip);
        }
    }

    private function _parseStringTpArray($string='') {
        $_pairs = array();
        if (is_string($string)) {
            $_units = explode(',', $string);
            foreach ($_units as $_unit) {
                $_pairs[] = trim($_unit);
            }
            unset($_units);
        }
        return $_pairs;
    }

    public function getDebugSql()
    {

    }


}