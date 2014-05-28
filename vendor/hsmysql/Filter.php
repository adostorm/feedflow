<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-29
 * Time: 上午1:46
 */

namespace HsMysql;


class Filter {

    private $_metas = array();

    public function getMetas() {
        return $this->_metas;
    }

    public function add($filter) {
        $this->_metas[] = $filter;
        return $this;
    }

}