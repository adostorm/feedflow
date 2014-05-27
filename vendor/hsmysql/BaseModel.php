<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-27
 * Time: 下午12:30
 */

namespace HsMysql;

final class T {
    const READ = 1;
    const WRITE = 2;
}

class BaseModel {

    private $tbname = '';

    private $primary = '';

    private $limit = 1;

    private $offset = 0;

    private $field = array();

    private $filter = null;

    private $inValues = null;

    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    public function field($field)
    {
        $this->field = $this->_parseStringToArray($field);
        return $this;
    }

    public function filter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function inValues($inValues)
    {
        $this->inValues = $inValues;
        return $this;
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

    }

    public function find($key, $operate=O::EQ) {
        $config = $this->_parseConfig(T::READ);
        $hsModel = HsModel::init($config['host'], $config['port'], $config['db'], $this->tbname, $this->primary);
        $result = $hsModel->find($key, $this->field, $operate, $this->offset, $this->limit, $this->filter, $this->inValues);

        if($this->debug) {
            $chunks = array('');
            foreach($this->filter as $filter) {
                $chunks[] = implode('', $filter);
            }

            $this->_sql = sprintf('SELECT %s FROM `%s` WHERE 【INDEX%s%s】 %s %s;'
                ,implode(',', $this->field) , $this->tbname, $operate, $key, implode(' AND ',$chunks), $this->inValues ? ' AND【INDEX】 IN ('.implode(',', $this->inValues).')':'');
        }

        return $result;
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