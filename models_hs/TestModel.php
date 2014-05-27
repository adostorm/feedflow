<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-27
 * Time: 下午5:08
 */

use HsMysql\CModel;

class TestModel {

    private $_DI = null;

    private $_link = 'link_db_countstate';

    private $_tbname = 'user_count_0';

    public function __construct($DI) {
        $this->_DI = $DI;
    }

    public function getCountByUid($uid) {
        $model = $this->_getModel();
        $result = $model->setField('uid,follow_count,fans_count')->find($uid);
        $model->trace();
        return $result;
    }

    public function removeCount($uid) {
        $model = $this->_getModel();
        $result = $model->delete($uid);
        $model->trace();
        return $result;
    }

    public function updateCount($uid) {
        $model = $this->_getModel();
        $result = $model->update($uid, array(
            'follow_count'=>100,
            'fans_count'=>89
        ));
        $model->trace();
        return $result;
    }

    public function addCount() {
        $model = $this->_getModel();
        $result = $model->insert(array(
            'uid'=>11,
            'follow_count'=>10,
            'fans_count'=>6,
        ));
        $model->trace();
        return $result;
    }

    private function _getModel() {
        $model = CModel::init($this->_DI, $this->_link, $this->_tbname);
        $model->setReport(true);
        return $model;
    }

}