<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-24
 * Time: 上午9:17
 */

namespace Util;


final class Partition {

    private $_link = '';

    private $_tbname = '';

    private $_pLink = '';

    private $_pTbname = '';

    private $_partition = array();

    private static $_instance = null;

    public function init($pLink, $pTbname, $partition) {
        $this->_pLink = $pLink;
        $this->_pTbname = $pTbname;
        $this->_partition = $partition;
        return $this;
    }

    private function __construct() {}
    private function __clone(){}

    public static function getInstance() {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function run($id) {
        static $cacheTable = array();
        static $cacheDb = array();
        if (!isset($cacheTable[$this->_pTbname.$id]) && is_array($this->_partition) && $this->_partition) {
            if ($this->_partition['mode'] == 'mod') {
                $ret = $id % $this->_partition['step'];
                $this->_tbname = $this->_pTbname.'_' . $ret;
            } else if ($this->_partition['mode'] == 'range') {
                $steps = $this->_partition['step'];
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
                $this->_tbname = $this->_pTbname.'_' . $num;

                if ($num > $this->_partition['limit']) {
                    $this->_link = sprintf('%s_%d', $this->_pLink, $num / $this->_partition['limit']);
                } else {
                    $this->_link = $this->_pLink;
                }

                $cacheTable[$this->_pTbname.$id] = $this->_tbname;
                $cacheDb[$this->_pLink.$id] = $this->_link;
            }
        } else {
            $this->_tbname = $cacheTable[$this->_pTbname.$id];
            $this->_link = $cacheDb[$this->_pLink.$id];
        }
        return $this;
    }

    public function getTbname() {
        return $this->_tbname;
    }

    public  function getLink() {
        return $this->_link;
    }

}