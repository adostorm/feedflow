<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-1
 * Time: 上午1:39
 */

namespace HsMysql;

use Phalcon\Exception;
use Util\ReadConfig;

class Model
{

    const READ_PORT = 1;

    const WRITE_PORT = 2;

    const SELECT = 1;

    const INSERT = 2;

    const UPDATE = 3;

    const DELETE = 4;

    public $index = null;

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

    public $partition = array();

    /**
     * @return null
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * @param boolean $isAssociate
     */
    public function setIsAssociate($isAssociate)
    {
        $this->isAssociate = $isAssociate;
        return $this;
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
            $config['dbname'] = ReadConfig::get("{$link}.dbname", $this->di);

            $config['host'] = ReadConfig::get("{$link}.host", $this->di);
            $slave = ReadConfig::get("{$link}.slave", $this->di)->toArray();

            if($readOrWrite == self::READ_PORT && $slave) {
                $randSlave = array_rand($slave);
                $config['host'] = $randSlave['host'];
                $config['port'] = ReadConfig::get("{$link}.hs_read_port", $this->di);
                $config['password'] = ReadConfig::get("{$link}.hs_read_passwd", $this->di);
            } else if ($readOrWrite == self::READ_PORT) {
                $config['port'] = ReadConfig::get("{$link}.hs_read_port", $this->di);
                $config['password'] = ReadConfig::get("{$link}.hs_read_passwd", $this->di);
            } else if ($readOrWrite == self::WRITE_PORT) {
                $config['port'] = ReadConfig::get("{$link}.hs_write_port", $this->di);
                $config['password'] = ReadConfig::get("{$link}.hs_write_passwd", $this->di);
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
        $this->_parsePartition($key);
        try {
            $handlersocket = $handler->initOpenIndex(self::SELECT, $this->tbname, $this->index, $this->fields, $this->indexFilter);
            $result = $handlersocket->executeSingle(self::SELECT, $op, array($key), $this->limit, $this->offset, null, null, $this->whereFilter);
            if($result===false) {
                echo ($handlersocket->getError());
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }

        $result = $this->_parseData($result);
        return $result;
    }

    public function update($key, $data, $op = '=')
    {
        $this->_parsePartition($key);
        $this->_parseFilter();
        $_fields = array();
        $_values = array();
        foreach ($data as $_field => $_value) {
            $_fields[] = $_field;
            $_values[] = $_value;
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::UPDATE, $this->tbname, $this->index, $_fields, $this->indexFilter);
            $result = $handlersocket->executeUpdate(self::UPDATE, $op, array($key), $_values, $this->limit, $this->offset, $this->whereFilter);
            if($result===false) {
                var_dump($handlersocket->getError());
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
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
        $this->_parsePartitionByInsert($data);

        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::INSERT, $this->tbname, $this->index, $_fields);
            $result = $handlersocket->executeInsert(self::INSERT, $_values);
            if($result===false) {
                echo ($handlersocket->getError());
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }

    public function delete($key, $op = '=')
    {
        $this->_parsePartition($key);
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

    public function increment($key, $data) {
        return $this->_countuPdate($key, $data, '+');
    }

    public function decrement($key, $data) {
        return $this->_countuPdate($key, $data, '-');
    }

    private function _countuPdate($key, $data, $mode='+', $op='=') {
        $this->_parseFilter();
        $_fields = array();
        $_values = array();
        foreach ($data as $_field => $_value) {
            $_fields[] = $_field;
            $_values[] = $_value;
        }
        $this->field($_fields);
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::UPDATE, $this->tbname, $this->index, $_fields, $this->indexFilter);
            $result = $handlersocket->executeSingle(self::UPDATE, $op, array($key), $this->limit, $this->offset, $mode, $_values, $this->whereFilter);
            if($result===false) {
                echo ($handlersocket->getError());
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        $result = $this->_parseData($result);
        return $result;
    }

    public function multi()
    {

    }

    public function _parsePartitionByInsert($data) {
        if($this->partition) {
            if($this->partition['mode']=='mod') {
                if(isset($data[$this->partition['field']])) {
                    $ret = $data[$this->partition['field']]%$this->partition['step'];
                    $this->tbname .= '_'.$ret;
                }

            }
        }
    }

    public function _parsePartition($id) {
        if(is_array($this->partition) && $this->partition) {
            if($this->partition['mode']=='mod') {
                $ret = $id%$this->partition['step'];
                $this->tbname .= '_'.$ret;
            }
        }
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
        } else {
            return $string;
        }
        return $_pairs;
    }

    public function getDebugSql()
    {

    }


}