<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-28
 * Time: 下午10:20
 */

namespace HsMysql;


class Meta {

    private $_metas = array();


    /**
     * @param string | array $field
     * @return $this
     */
    public function setField($field)
    {
        $this->_metas['field'] = $field;
        return $this;
    }


    /**
     * @param $key
     * @param string $op
     * @return $this
     */
    public function setKey($key, $op='=')
    {
        $this->_metas['key'] = $key;
        $this->_metas['op'] = $op;
        return $this;
    }

    public function add($filter) {
        $this->_metas['filter'][] = array(
            'filter'=>$filter,
        );
        return $this;
    }

    public function getMultiMeta() {
        return $this->_metas;
    }

}