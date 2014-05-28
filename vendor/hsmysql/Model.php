<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-1
 * Time: 上午1:39
 */

namespace HsMysql;

use Phalcon\Exception;
use Util\Partition;
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

    private $pTbname = '';

    private $pDbname = '';

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

    public $isChangedPartition = false;

    public $logger = null;

    public $in_values = null;

    /**
     * @return null
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * @param $isAssociate
     * @return $this
     */
    public function setIsAssociate($isAssociate)
    {
        $this->isAssociate = $isAssociate;
        return $this;
    }

    public function __construct($di, $link = '')
    {
        $this->di = $di;
        $this->logger = \Util\Logger::init($di);
        $this->_parseName($link);
    }

    private function _parseName($link)
    {
        if (stripos($link, '.') > 0) {
            list($dbname, $tbname) = explode('.', $link);
            $this->dbname = $this->pDbname = $dbname;
            $this->tbname = $this->pTbname = $tbname;
        }
    }

    private function _parseConfig($readOrWrite = self::WRITE_PORT)
    {
        static $cacheConfig = array();

        if (!$this->dbname) {
            throw new \Exception('error#-99 : no dbname');
        } else if (!$this->tbname) {
            throw new \Exception('error#-98 : no tbname');
        }

        $link = sprintf('link_%s', $this->dbname);
        $key = $link . $readOrWrite;
        if (is_object($this->di) && !isset($cacheConfig[$key])) {
            $config = array();
            if ($readOrWrite == self::READ_PORT) {
                $slaves = ReadConfig::get("{$link}.slaves", $this->di)->toArray();
                if($slaves) {
                    $rnd = array_rand($slaves);
                    $config['host'] = $slaves[$rnd]['host'];
                    $config['dbname'] = $slaves[$rnd]['dbname'];
                    $config['password'] = $slaves[$rnd]['hs_read_passwd'];
                    $config['port'] = $slaves[$rnd]['hs_read_port'];
                } else {
                    $config['host'] = ReadConfig::get("{$link}.host", $this->di);
                    $config['dbname'] = ReadConfig::get("{$link}.dbname", $this->di);
                    $config['password'] = ReadConfig::get("{$link}.hs_read_passwd", $this->di);
                    $config['port'] = ReadConfig::get("{$link}.hs_read_port", $this->di);
                }
            } else if ($readOrWrite == self::WRITE_PORT) {
                $config['host'] = ReadConfig::get("{$link}.host", $this->di);
                $config['dbname'] = ReadConfig::get("{$link}.dbname", $this->di);
                $config['password'] = ReadConfig::get("{$link}.hs_write_passwd", $this->di);
                $config['port'] = ReadConfig::get("{$link}.hs_write_port", $this->di);
            }

            $cacheConfig[$key] = $config;
        }
        return $cacheConfig[$key];
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

    public function setPartition($changePartitionKey = 0)
    {
        if ($changePartitionKey) {
            $this->_parsePartition($changePartitionKey);
            $this->isChangedPartition = true;
        }
        return $this;
    }

    public function in($keys) {
        if(is_array($keys) && $keys) {
            $this->in_values = $keys;
        }
        return $this;
    }

    public function find($key = '', $op = '=')
    {
        $this->_parseFilter();
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::READ_PORT));
        if (!$this->isChangedPartition) {
            $this->_parsePartition($key);
        }
        try {
            $in_key = -1;
            if($this->in_values) {
                $this->limit = count($this->in_values) + 1;
                $in_key = 0;
            }
            $handlersocket = $handler->initOpenIndex(self::SELECT, $this->pTbname, $this->index, $this->fields, $this->indexFilter);
            $result = $handlersocket->executeSingle(self::SELECT, $op, array($key), $this->limit, $this->offset, null, null, $this->whereFilter, $in_key, $this->in_values);
            $this->in_values = null;
            $this->isChangedPartition = false;
            if (false === $result) {
                $this->logger->log($handlersocket->getError(), \Phalcon\Logger::ERROR);
            }
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), \Phalcon\Logger::ERROR);
        }

        $result = $this->_parseData($result);
        $this->limit = 1;
        return $result;
    }

    public function update($key, $data, $op = '=')
    {
        $this->_parsePartition($key);
        $this->_parseFilter();
        $pairs = $this->_parseFieldsAndValues($data);
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::UPDATE, $this->pTbname, $this->index, $pairs['fields'], $this->indexFilter);
            $result = $handlersocket->executeUpdate(self::UPDATE, $op, array($key), $pairs['values'], $this->limit, $this->offset, $this->whereFilter);
            if (false === $result) {
                $this->logger->log($handlersocket->getError(), \Phalcon\Logger::ERROR);
            }
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), \Phalcon\Logger::ERROR);
        }
        return $result;
    }

    public function insert($data)
    {
        $pairs = $this->_parseFieldsAndValues($data);
        $this->_parsePartitionByInsert($data);

        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::INSERT, $this->pTbname, $this->index, $pairs['fields']);
            $result = $handlersocket->executeInsert(self::INSERT, $pairs['values']);
            echo $this->pTbname;
            echo PHP_EOL;
            var_dump(self::INSERT, $this->pTbname, $this->index, $pairs['fields']);
            echo PHP_EOL;
            if (false === $result) {
                $this->logger->log($handlersocket->getError(), \Phalcon\Logger::ERROR);
            }
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), \Phalcon\Logger::ERROR);
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
        $handlersocket = $handler->initOpenIndex(self::DELETE, $this->pTbname, $this->index, $field, $this->indexFilter);
        $result = $handlersocket->executeDelete(self::DELETE, $op, array($key), $this->limit, $this->offset, $this->whereFilter);
        if (false === $result) {
            $this->logger->log($handlersocket->getError(), \Phalcon\Logger::ERROR);
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

    private function _countUpdate($key, $data, $mode = '+', $op = '=')
    {
        $this->_parsePartition($key);
        $this->_parseFilter();
        $pairs = $this->_parseFieldsAndValues($data);
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        try {
            $handlersocket = $handler->initOpenIndex(self::UPDATE, $this->pTbname, $this->index, $pairs['fields'], $this->indexFilter);
            $result = $handlersocket->executeSingle(self::UPDATE, $op, array($key), $this->limit, $this->offset, $mode, $pairs['values'], $this->whereFilter);
            if (false === $result) {
                $this->logger->log($handlersocket->getError(), \Phalcon\Logger::ERROR);
            }
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), \Phalcon\Logger::ERROR);
        }
        $result = $this->_parseData($result);
        return $result;
    }

    public function _parsePartitionByInsert($data)
    {
        $this->pTbname = $this->tbname;
        if (isset($this->partition['field']) && isset($data[$this->partition['field']])) {
            $this->_parsePartition($data[$this->partition['field']]);
        }
    }

    public function _parsePartition($id)
    {
        $_pr = \Util\Partition::getInstance()
                    ->init($this->dbname, $this->tbname, $this->partition);
        $_pr->run($id);

        $this->pDbname = $_pr->getPartDbname();
        $this->pTbname = $_pr->getPartTbname();
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

    private function _parseStringTpArray($string = '')
    {
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

    private function _parseFieldsAndValues($data)
    {
        return array(
            'fields' => array_keys($data),
            'values' => array_values($data),
        );
    }

    public function getDebugSql()
    {

    }


}