<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-9
 * Time: 上午2:35
 */

class AdvModel extends \Phalcon\Mvc\Model {

    public $partition = array();

    public $tbname = '';

    public $dbname = '';

    public function init($id) {
        $this->_parsePartition($id);
        $this->setConnectionService($this->dbname);
        $this->setSource($this->tbname);
    }

    private function _parsePartition($id) {
        static $cachePhTables = array();

        if(!isset($cachePhTables[$this->tbname]) && is_array($this->partition) && $this->partition) {
            if($this->partition['mode']=='mod') {
                $ret = $id%$this->partition['step'];
                $this->tbname .= '_'.$ret;
            } else if($this->partition['mode']=='range') {
                $steps = $this->partition['step'];
                $count = sizeof($steps);
                $num = 0;
                for($i=0;$i<$count;$i++) {
                    if(($i+1) == $count) {//boundary
                        $num = $i;
                    } else if($id>=$steps[$i] && $id<$steps[$i+1]) {
                        $num = $i;
                        break;
                    }
                }
                $this->tbname .= '_'.$num;

                $cachePhTables[$this->tbname] = $this->tbname;

                if($num > $this->partition['limit']) {
                    $this->dbname = sprintf('link_%s_%d', $this->dbname, $num/$this->partition['limit']);
                } else {
                    $this->dbname = sprintf('link_%s', $this->dbname);
                }
            }
        }
    }

}