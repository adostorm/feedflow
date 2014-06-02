<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-26
 * Time: 下午11:03
 */

namespace HsMysql;

use HsMysql\Operate;
use HsMysql\CriteriaCollection;


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
        'stmtnum' => 'Either field has not founded or [table | data] was not exists',
        'op'=>'Invalid operate character, Just support "=", "<", "<=", ">", ">="',
        'filterfld' => 'Require openIndex filter string',
        'xx' => '[handlersocket] unable to connect 1:1',

        ### 自定义的错误类型
        '_empty_columnfld'=>'Require a field, eg: SELECT [field1, field2, ...] FROM [table] [where ...]',
        '_empty_multi_args'=>'Require criteria collection, Just search support and insert | update | delete is ignored',
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

    public function find($key, $columns, $operate = Operate::EQ, $offset = 0, $limit = 1, $filters = array(), $inValues = array())
    {
        if(!$columns) {
            $this->_error = '_empty_columnfld';
            return false;
        }

        $_parser = $this->_parseFilters($filters);
        $in_key = -1;
        if ($inValues) {
            $in_key = 0;
        }
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $columns
            , $_parser['filterField']);

        $result = $_handler->executeSingle(
            $this->_autoIndex
            , $operate
            , array($key)
            , $limit
            , $offset
            , null
            , null
            , $_parser['filter']
            , $in_key
            , $inValues);

        $result = $this->_parseAssociate($result, $columns);

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

    private function _countUpdate($key, $data, $mode = '+', $operate = Operate::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , array_keys($data)
            , $_parser['filterField']);

        $result = $_handler->executeSingle(
            $this->_autoIndex
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

    public function update($key, $data, $operate = Operate::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , array_keys($data)
            , $_parser['filterField']);

        $result = $_handler->executeUpdate(
            $this->_autoIndex
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

    public function delete($key, $operate = Operate::EQ, $offset = 0, $limit = 1, $filters = array())
    {
        $_parser = $this->_parseFilters($filters);
        $_handler = $this->getHandlerSocketCache();

        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $_parser['filterField']
            , $_parser['filterField']);

        $result = $_handler->executeDelete(
            $this->_autoIndex
            , $operate
            , array($key)
            , $limit
            , $offset
            , $_parser['filter']);

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

        if (false === $result) {
            $this->_error = $_handler->getError();
        }
        return $result;
    }

    public function multi($columns, CriteriaCollection $criteriaCollection) {
        if(!$columns) {
            $this->_error = '_empty_columnfld';
            return false;
        }

        $args = array();
        foreach($criteriaCollection->toArray() as $criteria) {
            if(!$criteria->getUpdate()) {
                $_parser = $this->_parseMultiFilters(
                    $criteria->getFilters(),
                    $criteriaCollection->getFilterFields()
                );
                $args[] = array(
                    $this->_autoIndex,
                    $criteria->getOperate(),
                    array($criteria->getKey()),
                    (int) $criteria->getLimit(),
                    (int) $criteria->getOffset(),
                    $criteria->getUpdate(),
                    $criteria->getValues(),
                    $_parser,
                    (int) $criteria->getInKey(),
                    $criteria->getInValues(),
                );
            }
        }

        if(!$args) {
            $this->_error = '_empty_multi_args';
            return false;
        }

        $_handler = $this->getHandlerSocketCache();
        $_handler->openIndex(
            $this->_autoIndex
            , $this->_config['dbname']
            , $this->_config['tbname']
            , $this->_config['primary']
            , $columns
            , $criteriaCollection->getFilterFields()
        );

        $result = $_handler->executeMulti($args);

        $result = $this->_parseMultiAssociate($result, $columns);

        $this->_error = $_handler->getError();
        return $result;
    }

    private function _parseCommonAssociate($result, $fields) {
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

    private function _parseAssociate($result, $fields) {
        if(!$this->_isAssociate || !is_array($result) || !$result) {
            return $result;
        }
        return $this->_parseCommonAssociate($result, $fields);
    }

    private function _parseMultiAssociate($result, $fields) {
        if(!$this->_isAssociate) {
            return $result;
        }
        $rets = array();
        foreach($result as $row) {
            if($row) {
                $rets[] = $this->_parseCommonAssociate($row, $fields);
            } else {
                $rets[] = $row;
            }
        }
        return $rets;
    }

    public function parseMultiAssemble($result, $isAssemble=true) {
        if($isAssemble) {
            $rets = array();
            foreach($result as $row) {
                if($row) {
                    foreach($row as $_sub) {
                        $rets[] = $_sub;
                    }
                }
            }
            return $rets;
        }
        return $result;
    }

    private function _parseFilters($filters)
    {
        $_f1 = null;
        $_f2 = null;
        foreach ($filters as $k=>$filter) {
            $_f1[] = strval($filter[0]);
            $_f2[] = array('F', $filter[1], $k, $filter[2]);
        }

        return array(
            'filterField' => $_f1,
            'filter' => $_f2,
        );
    }

    private function _parseMultiFilters($filters, $filterField) {
        $_f = null;
        foreach ($filters as $filter) {
            $k = array_search($filter[0], $filterField);
            $_f[] = array('F', $filter[1], $k, $filter[2]);
        }
        return $_f;
    }

    private function _highlight($text, $num) {
        if(PHP_SAPI == 'cli') {
            return chr(27).'['.$num.'m'.$text.chr(27).'[0m';
        }
        return $text;
    }

    public function getTraces()
    {
        $_traces = array(
            'HOST:'=>$this->_config['host'],
            'PORT:'=>$this->_config['port'],
            'DATABASE STATUS:'=>'',
            'DATABASE NAME:'=>$this->_config['dbname'],
            'TABLE NAME:'=>$this->_config['tbname'],
            'CONNECT ID:'=>$this->_autoIndex,
            'CONSTRAINT:'=>$this->_config['primary'],
            'ERROR INFO:'=>'',
            'EXECUTE STATUS:'=>'',
            'EXECUTE SQL:'=>'',
            'EXECUTE RESULT:'=>'',
        );

        $redColor = 31;
        $greenColor = 32;

        if(is_array($this->_error)) {
            $_traces[''] = str_pad('', 20, '=')." multi information ".str_pad('', 20, '=');
            foreach($this->_error as $k=>$_err) {
                if($_err) {
                    $_traces['EXECUTE STATUS '.$k.':'] = $this->_highlight('ERROR', $redColor);

                    if (isset($this->_errorInfos[$_err])) {
                        $errormsg = $_err
                            . ' @('
                            . $this->_errorInfos[$_err]
                            . ', Please check it and then try again.)';
                    } else {
                        $errormsg = $_err;
                    }

                    $_traces['ERROR INFO '.$k.':'] =
                        $this->_highlight($errormsg, $redColor);
                }
            }
        } else if($this->_error) {
            $_traces['EXECUTE STATUS :'] = $this->_highlight('ERROR', $redColor);
            if (isset($this->_errorInfos[$this->_error])) {
                $errormsg = $this->_error
                    . ' @('
                    . sprintf($this->_errorInfos[$this->_error], $this->_config['primary'])
                    . ', Please check it and then try again.)';
            } else {
                $errormsg = $this->_error;
            }
            $_traces['ERROR INFO:'] = $this->_highlight($errormsg, $redColor);
        }

        if(empty($_traces['EXECUTE STATUS:'])) {
            $_traces['EXECUTE STATUS:'] = $this->_highlight('SUCCESS', $greenColor).' @(If result equal 0 or empty that Maybe the data was not exists.)';
            $_traces['ERROR INFO:'] = '';
        }

        return $_traces;
    }

    public function trace()
    {

        $_traces = $this->getTraces();

        echo PHP_EOL;

        echo str_pad('', 31, '*')
            . ' DEBUG INFORMATION '
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