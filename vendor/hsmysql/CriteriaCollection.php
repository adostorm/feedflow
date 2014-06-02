<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-31
 * Time: 下午10:00
 */

namespace HsMysql;

use HsMysql\Criteria;

final class CriteriaCollection {

    private $_collection = array();

    private $_filterFields = array();

    private $_isAssemble = true;

    /**
     * @param boolean $isAssemble
     */
    public function setIsAssemble($isAssemble)
    {
        $this->_isAssemble = $isAssemble;
    }

    /**
     * @return boolean
     */
    public function getIsAssemble()
    {
        return $this->_isAssemble;
    }

    public function add(Criteria $criteria) {
        $_filter = $criteria->getFilters();
        if($_filter) {
            foreach($_filter as $_f) {
                if(isset($_f[0]) && !isset($this->_filterFields[$_f[0]])) {
                    $this->_filterFields[$_f[0]] = $_f[0];
                }
            }
        }
        $this->_collection[] = $criteria;
    }

    public function getFilterFields() {
        return $this->_filterFields;
    }

    public function toArray() {
        return $this->_collection;
    }

}