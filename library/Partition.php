<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-24
 * Time: 上午9:17
 */

namespace Util;


final class Partition {

    private $dbname = '';

    private $tbname = '';

    private $pDbname = '';

    private $pTbname = '';

    private $partition = array();

    private static $instance = null;

    public function init($dbname, $tbname, $partition) {
        $this->dbname = $dbname;
        $this->tbname = $tbname;
        $this->partition = $partition;
        return $this;
    }

    private function __construct() {}
    private function __clone(){}

    public static function getInstance() {
        if(null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run($id) {
        static $cacheTable = array();
        static $cacheDb = array();
        static $_tmp_tb = '';
        static $_tmp_db = '';
        if (!isset($cacheTable[$this->tbname.$id]) && is_array($this->partition) && $this->partition) {
            if(!$_tmp_tb) {
                $_tmp_tb = $this->tbname;
                $_tmp_db = $this->dbname;
            }
            if ($this->partition['mode'] == 'mod') {
                $ret = $id % $this->partition['step'];
                $this->pTbname = $_tmp_tb.'_' . $ret;
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
                $this->pTbname = $_tmp_tb.'_' . $num;

                if ($num > $this->partition['limit']) {
                    $this->pDbname = sprintf('link_%s_%d', $_tmp_db, $num / $this->partition['limit']);
                } else {
                    $this->pDbname = sprintf('link_%s', $_tmp_db);
                }

                $cacheTable[$_tmp_tb.$id] = $this->pTbname;
                $cacheDb[$_tmp_db.$id] = $this->pDbname;
            }
        } else {
            $this->pTbname = $cacheTable[$_tmp_tb.$id];
            $this->pDbname = $cacheDb[$_tmp_db.$id];
        }
    }

    public function getPartTbname() {
        return $this->pTbname;
    }

    public  function getPartDbname() {
        return $this->pDbname;
    }

}