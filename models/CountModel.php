<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class CountModel extends \Phalcon\Mvc\Model {

    public function getCountByUid($uid) {
        $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
        $model = $proxy->getHandlerSocketModel(IConfig::READ_PORT);
        $index = $model->createIndex(Model::SELECT, 'user_count', 'primary', array('uid','user_name','follow_count', 'fans_count', 'feed_count'));

        $result = $index->find($uid);

        if($result && isset($result[0])) {
            $result = $result[0];
        }

        return $result;
    }

    public function updateCount($uid, $updateField='', $num='') {

    }

}