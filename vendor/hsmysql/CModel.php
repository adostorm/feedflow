<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-27
 * Time: 下午12:30
 */

namespace HsMysql;

use Util\Partition;
use Util\ReadConfig;
use HsMysql\Op;
use HsMysql\Mode;

final class T {
    const READ = 1;
    const WRITE = 2;
}

class CModel {

    private $_tbname = '';

    private $_primary = 'PRIMARY';

    private $_limit = 1;

    private $_offset = 0;

    private $_field = '';

    private $_filter = null;

    private $_inValues = null;

    private $_sql = '';

    private $_traces = array();

    private $_debug = false;

    private $_report = false;

    private $_DI = null;

    private $_link = '';

    private static $_instance = null;

    /**
     * @param $DI
     * @return $this
     */
    public function setDI($DI)
    {
        $this->_DI = $DI;
        return $this;
    }

    /**
     * @param $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
        return $this;
    }

    /**
     * @param $report
     * @return $this
     */
    public function setReport($report)
    {
        $this->_report = $report;
        return $this;
    }

    /**
     * @param $field
     * @return $this
     */
    public function setField($field)
    {
        $this->_field = $this->_parseStringToArray($field);
        return $this;
    }

    /**
     * @param $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;
        return $this;
    }

    /**
     * @param $inValues
     * @return $this
     */
    public function setInValues($inValues)
    {
        $this->_inValues = $inValues;
        return $this;
    }

    /**
     * @param $tbname
     * @return $this
     */
    public function setTbname($tbname)
    {
        $this->_tbname = $tbname;
        return $this;
    }

    /**
     * @param $primary
     * @return $this
     */
    public function setPrimary($primary)
    {
        $this->_primary = $primary;
        return $this;
    }

    public function setLimit($offset=0, $limit=1)
    {
        $this->_offset = $offset;
        $this->_limit = $limit;
        return $this;
    }

    public static function init($di, $link, $tbname) {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        self::$_instance->_DI = $di;
        self::$_instance->_link = $link;
        self::$_instance->_tbname = $tbname;
        return self::$_instance;
    }

    private function _parseStringToArray($string = '')
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

    private function _parseConfig($mode=T::READ) {
        static $cacheConfig = array();
        $key = $this->_link . $mode;
        if (is_object($this->_DI) && !isset($cacheConfig[$key])) {
            $config = array();
            if ($mode == T::READ) {
                $slaves = ReadConfig::get("{$this->_link}.slaves", $this->_DI)->toArray();
                if($slaves) {
                    $rnd = array_rand($slaves);
                    $config['host'] = $slaves[$rnd]['host'];
                    $config['dbname'] = $slaves[$rnd]['dbname'];
                    $config['password'] = $slaves[$rnd]['hs_read_passwd'];
                    $config['port'] = $slaves[$rnd]['hs_read_port'];
                } else {
                    $config['host'] = ReadConfig::get("{$this->_link}.host", $this->_DI);
                    $config['dbname'] = ReadConfig::get("{$this->_link}.dbname", $this->_DI);
                    $config['password'] = ReadConfig::get("{$this->_link}.hs_read_passwd", $this->_DI);
                    $config['port'] = ReadConfig::get("{$this->_link}.hs_read_port", $this->_DI);
                }
            } else if ($mode == T::WRITE) {
                $config['host'] = ReadConfig::get("{$this->_link}.host", $this->_DI);
                $config['dbname'] = ReadConfig::get("{$this->_link}.dbname", $this->_DI);
                $config['password'] = ReadConfig::get("{$this->_link}.hs_write_passwd", $this->_DI);
                $config['port'] = ReadConfig::get("{$this->_link}.hs_write_port", $this->_DI);
            }
            $cacheConfig[$key] = $config;
        }
        return $cacheConfig[$key];
    }

    public function find($key, $operate=Op::EQ) {
        $config = $this->_parseConfig(T::READ);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->find($key, $this->_field, $operate, $this->_offset, $this->_limit, $this->_filter, $this->_inValues);
        $this->_info_('select', $hsModel, $operate, $key);
        return $result;
    }

    public function insert($data) {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->insert($data);
        $this->_info_('insert', $hsModel, $data);
        return $result;
    }

    public function update($key, $data, $operate=Op::EQ) {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->update($key, $data, $operate, $this->_offset, $this->_limit, $this->_filter);
        $this->_info_('update', $hsModel, $operate, $data, $key);
        return $result;
    }

    public function delete($key, $operate=Op::EQ) {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->delete($key, $operate, $this->_offset, $this->_limit, $this->_filter);
        $this->_info_('delete', $hsModel, $operate, $key);
        return $result;
    }

    private function _getHsModel($config) {
        return HsModel::init(array_merge($config, array(
            'tbname'=>$this->_tbname,
            'primary'=>$this->_primary,
        )));
    }

    public function __call($name, $arguments) {
        if($name == '_info_') {
            if($this->_report || $this->_debug) {
                $mode = $arguments[0];
                $hsModel = $arguments[1];
                $_traces = $hsModel->getTraces();
                $filterChunks = array('');
                if($this->_filter) {
                    foreach($this->_filter as $filter) {
                        $filterChunks[] = implode('', $filter);
                    }
                }

                switch($mode) {
                    case 'insert':
                        $data = $arguments[2];
                        $this->_sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s);'
                            , $_traces['TABLE NAME:']
                            , implode(',',array_keys($data))
                            , trim(implode(',',array_values($data))));
                        $_traces['EXECUTE SQL:'] = $this->_sql;
                        break;

                    case 'update':
                        $operate = $arguments[2];
                        $data = $arguments[3];
                        $key = $arguments[4];
                        $chunks2 = array('');
                        foreach($data as $k=>$v) {
                            $chunks2[] = $k.'='.$v;
                        }
                        $this->_sql = sprintf('UPDATE `%s` SET %s WHERE [INDEX%s%s] %s;'
                            , $_traces['TABLE NAME:']
                            , trim(implode(' , ',$chunks2),' , ')
                            , $operate
                            , $key
                            , trim(implode(' AND ', $filterChunks)));
                        $_traces['EXECUTE SQL:'] = $this->_sql;
                        break;

                    case 'delete':
                        $operate = $arguments[2];
                        $key = $arguments[3];
                        $this->_sql = sprintf('DELETE FROM `%s` WHERE [INDEX%s%s] %s;'
                            , $_traces['TABLE NAME:']
                            , $operate
                            , $key
                            , trim(implode(' AND ', $filterChunks)));
                        $_traces['EXECUTE SQL:'] = $this->_sql;
                        break;

                    case 'select':
                        $operate = $arguments[2];
                        $key = $arguments[3];

                        $in_chunk = $this->_inValues ? ' AND【INDEX】 IN ('.implode(',', $this->_inValues).')':'';
                        $this->_sql = trim(sprintf('SELECT %s FROM `%s` WHERE [INDEX%s%s] %s %s;'
                            ,implode(',', $this->_field)
                            , $_traces['TABLE NAME:']
                            , $operate
                            , $key
                            , implode(' AND ', $filterChunks)
                            , trim($in_chunk)));
                        $_traces['EXECUTE SQL:'] = $this->_sql;
                        break;
                }
                $this->_traces = $_traces;

                if($this->_debug) {
                    //save in file
                }
            }


        }
    }

    public function trace() {
        echo PHP_EOL;
        echo str_pad('', 31, '-').'DEBUG INFORMATION'.str_pad('', 32, '-').PHP_EOL;
        if(!$this->_traces) {
            echo '- PLEASE OPEN DEBUG MODE'.PHP_EOL;
        } else {
            foreach($this->_traces as $desc=>$info) {
                echo '- '.$desc.' '.$info.PHP_EOL;
            }
        }
        echo str_pad('', 80, '-').PHP_EOL;
        echo PHP_EOL;
        $this->_traces = array();
    }

}