<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-9
 * Time: 上午2:35
 */

class AdvModel extends \Phalcon\Mvc\Model
{

    public $partition = array();

    public $tbname = '';

    public $dbname = '';

    public function init($id)
    {
        $this->_parsePartition($id);
        $this->setWriteConnectionService($this->dbname);
        $conf = \Util\ReadConfig::get($this->dbname, $this->getDI());
        if(isset($conf->slaves) && $slaves = $conf->slaves->toArray()) {
            $rnd = array_rand($slaves);
            $this->setReadConnectionService($this->dbname.'_read_'.$rnd);
        }
        $this->setSource($this->tbname);
    }

    /**
     * 分库分表规则
     * @param $id
     */
    private function _parsePartition($id)
    {
        static $cacheTable = array();
        static $_tmp_tb = '';
        if (!isset($cacheTable[$this->tbname.$id]) && is_array($this->partition) && $this->partition) {
            if(!$_tmp_tb) {
                $_tmp_tb = $this->tbname;
            }
            if ($this->partition['mode'] == 'mod') {
                $ret = $id % $this->partition['step'];
                $this->tbname = $_tmp_tb.'_' . $ret;
            } else if ($this->partition['mode'] == 'range') {
                $steps = $this->partition['step'];
                $count = sizeof($steps);
                $num = 0;
                for ($i = 0; $i < $count; $i++) {
                    if (($i + 1) == $count) { //boundary
                        $num = $i;
                    } else if ($id >= $steps[$i] && $id < $steps[$i + 1]) {
                        $num = $i;
                        break;
                    }
                }
                $this->tbname = $_tmp_tb.'_' . $num;

                $cacheTable[$this->tbname.$id] = $this->tbname;

                if ($num > $this->partition['limit']) {
                    $this->dbname = sprintf('link_%s_%d', $this->dbname, $num / $this->partition['limit']);
                } else {
                    $this->dbname = sprintf('link_%s', $this->dbname);
                }
            }
        }
    }

}