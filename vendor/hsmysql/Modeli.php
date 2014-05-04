<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-1
 * Time: 上午1:39
 */

namespace HsMysql;

use Util\ReadConfig;

class Modeli
{

    const READ_PORT = 1;

    const WRITE_PORT = 2;

    const SELECT = 1;

    const INSERT = 2;

    const UPDATE = 3;

    const DELETE = 4;

    public $index = '';

    private $tbname = '';

    public $dbname = '';

    public $multi = false;

    public $filter = null;

    private $fields = '';

    private $limit = 1;

    private $offset = 0;

    private $indexFilter = null;

    private $whereFilter = null;

    public $adapter = 'Mysql';

    private $di = null;

    private $isAssociate = true;

    public $insertId = 0;

    public function __construct($link, $di)
    {
        $this->_parseName($link);
        $this->di = $di;
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
            $di = $this->di->get('config');
            $diLinkConfig = ReadConfig::get(sprintf('link_%s', $this->dbname), $di);
            $config['host'] = ReadConfig::get('host', $diLinkConfig);
            $config['password'] = ReadConfig::get('password', $diLinkConfig);
            $config['dbname'] = ReadConfig::get('dbname', $diLinkConfig);
            if ($readOrWrite == self::READ_PORT) {
                $config['port'] = ReadConfig::get('hs_read_port', $diLinkConfig);
            } else if ($readOrWrite == self::WRITE_PORT) {
                $config['port'] = ReadConfig::get('hs_write_port', $diLinkConfig);
            }
        }
        return $config;
    }

    public function field($field)
    {
        $_fileds = array();
        if (is_string($field)) {
            $fileds = explode(',', $field);
            foreach ($fileds as $_field) {
                $_fileds[] = trim($_field);
            }
        }
        $this->fields = $_fileds;
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
        $index = $handler->initCreateIndex(self::SELECT, $this->tbname, $this->index, $this->fields, $this->indexFilter);
        $result = array();
        try {
            $result = $index->find(array($op => $key), $this->limit, $this->offset, $this->whereFilter);
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
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
        $index = $handler->initCreateIndex(self::UPDATE, $this->tbname, $this->index, $_fields, $this->indexFilter);
        $result = array();
        try {
            $result = $index->update(array($op => $key), $_values, $this->limit, $this->offset, $this->whereFilter);
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }

    //这个方法不能有返回值
    /**
     * 这个方法官方提供存在问题，不推荐使用
     * @param $data
     *
     * Reason :
     *      1, recv() failed (104: Connection reset by peer) while reading response header from upstream
     *      2, exited on signal 11 (SIGSEGV - core dumped)
     */
    public function insert($data)
    {
        $_fields = array();
        $_values = array();
        foreach ($data as $_field => $_value) {
            $_fields[] = $_field;
            $_values[] = $_value;
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        $index = $handler->initCreateIndex(self::UPDATE, $this->tbname, $this->index, $_fields, $this->indexFilter);
        try {
            //result 不能在外层定义
            $this->insertId = $index->insert($_values);
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function delete($key, $op = '=')
    {
        $this->_parseFilter();
        $field = array();
        if (null !== $this->indexFilter) {
            $field = $this->indexFilter['filter'];
        }
        if (!$field) {
            throw new \Exception('no field , please set any field like this: $model->field([...]);');
        }
        $handler = \HsMysql\Handler::getInstance($this->_parseConfig(self::WRITE_PORT));
        $index = $handler->initCreateIndex(self::UPDATE, $this->tbname, $this->index, $field, $this->indexFilter);
        $result = array();
        try {
            $result = $index->remove(array($op => $key), $this->limit, $this->offset, $this->whereFilter);
        } catch (\HandlerSocketException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }


    public function getInsertId()
    {
        return $this->insertId;
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
            $this->indexFilter = array('filter' => array_unique($tempIndexFilter));
            $tempWhereFilter = array();
            foreach ($this->filter as $filter) {
                $tempWhereFilter = array($filter[1], $filter[0], $filter[2]);
            }
            $this->whereFilter = array('filter' => $tempWhereFilter);
            unset($tempIndexFilter, $tempWhereFilter);
        }
    }

    public function getDebugSql()
    {

    }


}