<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-27
 * Time: 下午12:30
 */

namespace HsMysql;

use Util\ReadConfig;
use HsMysql\Operate;
use HsMysql\CriteriaCollection;

final class T
{
    const READ = 1;
    const WRITE = 2;
}

class CModel
{
    private $_tbname = '';

    private $_primary = 'PRIMARY';

    private $_limit = 1;

    private $_offset = 0;

    private $_field = '';

    private $_filter = null;

    private $_inValues = null;

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


    public function setLimit($offset = 0, $limit = 1)
    {
        $this->_offset = $offset;
        $this->_limit = $limit;
        return $this;
    }

    public static function init($di, $link, $tbname)
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        self::$_instance->_setVariables($di, $link, $tbname);
        return self::$_instance;
    }

    private function _setVariables($di, $link, $tbname) {
        $this->_DI = $di;
        $this->_link = $link;
        $this->_tbname = $tbname;

        $this->_primary = 'PRIMARY';
        $this->_limit = 1;
        $this->_offset = 0;
        $this->_field = '';
        $this->_filter = null;
        $this->_inValues = null;
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

    private function _parseConfig($mode = T::READ)
    {
        static $cacheConfig = array();
        $key = $this->_link . $mode;

        if (!isset($cacheConfig[$key])) {
            $config = array();
            if ($mode == T::READ) {
                $slaves =
                    ReadConfig::get("{$this->_link}.slaves", $this->_DI)->toArray();
                $readSlave =
                    ReadConfig::get("open_read_slave", $this->_DI);
                if ($slaves && $readSlave) {
                    $rnd = array_rand($slaves);
                    $config['host'] = $slaves[$rnd]['host'];
                    $config['dbname'] = $slaves[$rnd]['dbname'];
                    $config['password'] = $slaves[$rnd]['hs_read_passwd'];
                    $config['port'] = $slaves[$rnd]['hs_read_port'];
                    $config['status'] = 'SLAVE';
                } else {
                    $config['host'] =
                        ReadConfig::get("{$this->_link}.host", $this->_DI);
                    $config['dbname'] =
                        ReadConfig::get("{$this->_link}.dbname", $this->_DI);
                    $config['password'] =
                        ReadConfig::get("{$this->_link}.hs_read_passwd", $this->_DI);
                    $config['port'] =
                        ReadConfig::get("{$this->_link}.hs_read_port", $this->_DI);
                    $config['status'] = 'MASTER';
                }
            } else if ($mode == T::WRITE) {
                $config['host'] =
                    ReadConfig::get("{$this->_link}.host", $this->_DI);
                $config['dbname'] =
                    ReadConfig::get("{$this->_link}.dbname", $this->_DI);
                $config['password'] =
                    ReadConfig::get("{$this->_link}.hs_write_passwd", $this->_DI);
                $config['port'] =
                    ReadConfig::get("{$this->_link}.hs_write_port", $this->_DI);
                $config['status'] = 'MASTER';
            }
            $cacheConfig[$key] = $config;
        }

        return $cacheConfig[$key];
    }

    public function find($key, $operate = Operate::EQ)
    {
        $config = $this->_parseConfig(T::READ);
        $hsModel = $this->_getHsModel($config);

        $result = $hsModel->find($key
            , $this->_field
            , $operate
            , $this->_offset
            , $this->_limit
            , $this->_filter
            , $this->_inValues);

        $this->_info_('select', $hsModel, $result, $operate, $key);
        return $result;
    }

    public function insert($data)
    {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->insert($data);

        $this->_info_('insert', $hsModel, $result,  $data);
        return $result;
    }

    public function update($key, $data, $operate = Operate::EQ)
    {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);

        $result = $hsModel->update($key
            , $data
            , $operate
            , $this->_offset
            , $this->_limit
            , $this->_filter);

        $this->_info_('update', $hsModel, $result, $operate, $data, $key);
        return $result;
    }

    public function delete($key, $operate = Operate::EQ)
    {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);

        $result = $hsModel->delete($key
            , $operate
            , $this->_offset
            , $this->_limit
            , $this->_filter);

        $this->_info_('delete', $hsModel, $result, $operate, $key);
        return $result;
    }

    public function increment($key, $data)
    {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->increment($key, $data);

        $this->_info_('counter', $hsModel, $result, '+', $data, $key);
        return $result;
    }

    public function decrement($key, $data)
    {
        $config = $this->_parseConfig(T::WRITE);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->decrement($key, $data);

        $this->_info_('counter', $hsModel, $result, '-', $data, $key);
        return $result;
    }

    /**
     * 由于主从分离，这个方法只提供查找, 忽略update, delete, insert的操作
     * @param CriteriaCollection $criteriaCollection
     * @return bool
     */
    public function multi(CriteriaCollection $criteriaCollection) {
        $config = $this->_parseConfig(T::READ);
        $hsModel = $this->_getHsModel($config);
        $result = $hsModel->multi($this->_field, $criteriaCollection);

        $this->_info_('multi', $hsModel, $result, $criteriaCollection);

        $result = $hsModel->parseMultiAssemble($result, $criteriaCollection->getIsAssemble());

        return $result;
    }

    private function _getHsModel($config)
    {
        return HsModel::init(array_merge($config, array(
            'tbname' => $this->_tbname,
            'primary' => $this->_primary,
        )));
    }

    public function __call($name, $arguments)
    {
        if ($name == '_info_') {

            $debug = ReadConfig::get('debug', $this->_DI);

            if ($debug) {

                $_traces = array(
                    'HOST:'=>'',
                    'PORT:'=>'',
                    'DATABASE STATUS:'=>'',
                    'DATABASE NAME:'=>'',
                    'TABLE NAME:'=>'',
                    'CONNECT ID:'=>'',
                    'CONSTRAINT:'=>'',
                    'ERROR INFO:'=>'',
                    'EXECUTE STATUS:'=>'',
                    'EXECUTE SQL:'=>'',
                    'EXECUTE RESULT:'=>'',
                );

                $mode = $arguments[0];
                $hsModel = $arguments[1];
                $result = var_export($arguments[2], true);

                $config = $hsModel->getConfig();
                $_traces = array_merge($_traces, $hsModel->getTraces());

                $_traces['EXECUTE RESULT:'] = $result;

                $filterChunks = array('');
                if ($this->_filter) {
                    foreach ($this->_filter as $filter) {
                        $filterChunks[] = implode('', $filter);
                    }
                }

                $_sql = '';
                switch ($mode) {
                    case 'insert':
                        $data = $arguments[3];

                        $_sql = trim(sprintf('INSERT INTO `%s` (%s) VALUES (%s)'
                            , $_traces['TABLE NAME:']
                            , implode(',', array_keys($data))
                            , implode(',', array_values($data)))).';';

                        $_traces['DATABASE STATUS:'] = 'WRITEABLE @'.$config['status'].'(INSERT)';

                        break;

                    case 'update':
                        $operate = $arguments[3];
                        $data = $arguments[4];
                        $key = $arguments[5];
                        $chunks2 = array('');
                        foreach ($data as $k => $v) {
                            $chunks2[] = $k . '=' . $v;
                        }
                        $_sql = trim(sprintf('UPDATE `%s` SET %s WHERE [INDEX %s %s]%s'
                            , $_traces['TABLE NAME:']
                            , trim(implode(' , ', $chunks2), ' , ')
                            , $operate
                            , $key
                            , implode(' AND ', $filterChunks))).';';

                        $_traces['DATABASE STATUS:'] = 'WRITEABLE @'.$config['status'].'(UPDATE)';
                        break;

                    case 'delete':
                        $operate = $arguments[3];
                        $key = $arguments[4];
                        $_sql = trim(sprintf('DELETE FROM `%s` WHERE [INDEX %s %s]%s'
                            , $_traces['TABLE NAME:']
                            , $operate
                            , $key
                            , implode(' AND ', $filterChunks))).';';

                        $_traces['DATABASE STATUS:'] = 'WRITEABLE @'.$config['status'].'(DELETE)';
                        break;

                    case 'select':
                        $operate = $arguments[3];
                        $key = $arguments[4];

                        $in_chunk = $this->_inValues
                            ? ' AND [INDEX] IN (' . implode(',', $this->_inValues) . ')'
                            : '';
                        $_sql = trim(sprintf('SELECT %s FROM `%s` WHERE [INDEX %s %s]%s%s'
                            , ($this->_field ? implode(',', $this->_field) : '')
                            , $_traces['TABLE NAME:']
                            , $operate
                            , $key
                            , implode(' AND ', $filterChunks)
                            , trim($in_chunk))).';';

                        $_traces['DATABASE STATUS:'] = 'READABLE @'.$config['status'].'(SELECT)';
                        break;

                    case 'counter':
                        $mode = $arguments[3];
                        $data = $arguments[4];
                        $key = $arguments[5];

                        $chunks2 = array('');
                        foreach ($data as $k => $v) {
                            $chunks2[] = $k . '=' . $k . $mode . $v;
                        }

                        $_sql = sprintf('UPDATE `%s` SET %s WHERE [INDEX %s %s]%s;'
                            , $this->_tbname
                            , trim(implode(' , ', $chunks2), ' , ')
                            , '='
                            , $key
                            , trim(implode(' AND ', $filterChunks)));

                        $_traces['DATABASE STATUS:'] = 'WRITEABLE @'.$config['status'].'(UPDATE)';
                        break;

                    case 'multi':
                        $criteriaCollection =  $arguments[3];

                        foreach($criteriaCollection->toArray() as $k=>$criteria) {

                            $filterChunks = array('');
                            foreach ($criteria->getFilters() as $filter) {
                                $filterChunks[] = implode('', $filter);
                            }

                            $in_chunk = $criteria->getInValues()
                                ? ' AND [INDEX] IN (' . implode(',', $criteria->getInValues()) . ')'
                                : '';
                            $_x_sql = trim(sprintf('SELECT %s FROM `%s` WHERE [INDEX %s %s]%s%s'
                                    , ($this->_field ? implode(',', $this->_field) : '')
                                    , $_traces['TABLE NAME:']
                                    , $criteria->getOperate()
                                    , $criteria->getKey()
                                    , implode(' AND ', $filterChunks)
                                    , trim($in_chunk))).';';


                            $_traces['EXECUTE SQL '.$k.':'] = $_x_sql;
                        }

                        $_traces['DATABASE STATUS:'] = 'READABLE @'.$config['status'].'(SELECT)';
                        break;
                }

                $_traces['EXECUTE SQL:'] = $_sql;

                $this->trace($_traces);

            }
        }
    }

    public function trace($_traces)
    {
        $_log = array();

        $_log[] = str_pad('', 31, '*')
            . ' DEBUG INFORMATION '
            . str_pad('', 32, '*');

        if (!$_traces) {
            $_log[] = '- PLEASE OPEN DEBUG MODE';
        } else {
            foreach ($_traces as $desc => $info) {
                $_log[] = str_pad($desc, 18, ' ', STR_PAD_LEFT) . ' ' . $info;
            }
        }

        $_log[] = PHP_EOL;

        $_logStr = implode(PHP_EOL, $_log);

        if(isset($_REQUEST['_report_']) && $_REQUEST['_report_']) {
            echo $_logStr;
        } else {
            $filePath = ReadConfig::get('application.path', $this->_DI).'log/'.date('Y-m-d').'.log';
            $pattern = array(
                '/'.chr(27).'\[\d+m'.'/'
            );
            $_logStr = preg_replace($pattern, '', $_logStr);
            error_log($_logStr, 3, $filePath);
        }

    }

}