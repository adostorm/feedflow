<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-27
 * Time: 下午5:08
 */

use HsMysql\CModel;
use HsMysql\AdvModel;
use HsMysql\T;
use Util\Partition;

class TestModel extends AdvModel {

    private $_DI = null;

    private $_link = 'link_db_countstate';

    private $_tbSuffix = 'user_count';

    private $_primary = 'PRIMARY';

    private $_partition = array(
        'field' => 'author_id',
        'mode' => 'range',
        'step' => array(1, 1000000, 2000000, 3000000, 4000000, 5000000,
            6000000, 7000000, 8000000, 9000000, 10000000, 11000000, 12000000,
            13000000, 14000000, 15000000, 16000000, 17000000, 18000000, 19000000,
            20000000, 21000000, 22000000, 23000000, 24000000, 25000000, 26000000,
            27000000, 28000000, 29000000, 30000000, 1000000000),
        'limit' => 399
    );

    public function __construct($DI) {
        $this->_DI = $DI;
    }

    public function getCountByUid($uid) {
        $model = $this->_getModel($uid);
        $result = $model->find($uid);
        $model->trace();
        return $result;
    }

    public function removeCount($uid) {
        $model = $this->_getModel($uid);
        $result = $model->delete($uid);
        $model->trace();
        return $result;
    }

    public function updateCount($uid) {
        $model = $this->_getModel($uid);
        $result = $model->update($uid, array(
            'follow_countss'=>100,
            'fans_count'=>89
        ));
        $model->trace();
        return $result;
    }

    public function addCount($uid) {
        $model = $this->_getModel($uid);
        $result = $model->insert(array(
            'uid'=>$uid,
            'follow_count'=>10,
            'fans_count'=>6,
            'feed_count'=>1,
        ));
        $model->trace();
        return $result;
    }

    public function incrCount($uid) {
        $model = $this->_getModel($uid);
        $result = $model->decrement($uid, array(
            'follow_count'=>1,
        ));
        $model->trace();
        return $result;
    }

    private function _getModel($key, $isPartition=true) {
        if($isPartition) {
            $partition = Partition::getInstance()
                ->init($this->_link, $this->_tbSuffix, $this->_partition)
                ->run($key);
            $model = CModel::init($this->_DI, $partition->getLink(), $partition->getTbname());
        } else {
            $model = CModel::init($this->_DI, $this->_link, $this->_tbSuffix);
        }
        $model->setPrimary($this->_primary);
        $model->setReport(true);
        return $model;
    }

}