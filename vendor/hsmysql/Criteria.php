<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-31
 * Time: 下午8:11
 */

namespace HsMysql;

final class Criteria {

    private $_operate = '=';

    private $_key = '';

    private $_limit = 1;

    private $_offset = 0;

    private $_update = null;

    private $_values = array();

    private $_filters = array();

    private $_in_key = -1;

    private $_in_values = array();


    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->_values = $values;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }

    /**
     * @param null $update
     */
    public function setUpdate($update)
    {
        $this->_update = $update;
    }

    /**
     * @return null
     */
    public function getUpdate()
    {
        return $this->_update;
    }

    /**
     * @param string $operate
     */
    public function setOperate($operate)
    {
        $this->_operate = $operate;
    }

    /**
     * @return string
     */
    public function getOperate()
    {
        return $this->_operate;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param array $in_values
     */
    public function setInValues($in_values)
    {
        $this->_in_values = $in_values;
    }

    /**
     * @return array
     */
    public function getInValues()
    {
        return $this->_in_values;
    }

    /**
     * @param int $in_key
     */
    public function setInKey($in_key)
    {
        $this->_in_key = $in_key;
    }

    /**
     * @return int
     */
    public function getInKey()
    {
        return $this->_in_key;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->_filters = $filters;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

}