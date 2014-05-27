<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task
{

    public function test0Action() {
//        $queue = \Util\BStalkClient::getInstance($this->getDI());
//        $queue->choose(\Util\ReadConfig::get('queue_keys.allfeeds', $this->getDI()));
//
//        $data = array(
//            "app_id" => 1,
//            "source_id" => 1,
//            "object_type" => 2,
//            "object_id" => 1341918,
//            "author_id" => 8048111,
//            "author" => "xjay",
//            "content" => "测试发帖。。。",
//            "create_at" => 1376712770,
//            "attachment" => "",
//            "extends" => array(
//                'fid' => 1,
//                'ishelp' => 1,
//                'groupname' => '爱相约',
//            ),
//        );
//
//        $queue->put(msgpack_pack($data));
//
//        $queue->disconnect();
//
//
//
//        exit;
        $userFeed = new UserFeed();
        $result = $userFeed->getFeedListByAppIdAndUid(1, 8048111);
        var_dump($result);
    }

    public function test1Action() {
        $userFeedCountModel = new UserFeedCountModel($this->getDi());
//        $userFeedCountModel->updateCount(1, 1, array(
//            'feed_count'=>1,
//            'unread_count'=>1,
//        ), 0, true);

        $results = $userFeedCountModel->getCountByUid(1, 1);


        $results = $userFeedCountModel->resetUnReadCount(1, 1);
        var_dump($results);
        exit;

        $uid = 1;
        $friend_uids = 2;
        $userRelationModel = new UserRelationModel($this->getDI());
        $results = $userRelationModel->getInRelationList($uid, $friend_uids);
        var_dump($results);
    }

    public function test2Action() {
        $userFeed = new UserFeed();
        $userFeed->getFeedListByAppIdAndUid(1, 1);
    }

    public function test3Action() {
        for($i=0;$i<2;$i++) {
            $k = time() + $i;
            $model = \HsMysql\HsModel::init('localhost', 9999, 'db_countstate', 'user_count_0');
            $result = $model->insert(array(
                'uid'=>$k,
                'follow_count'=>1,
                'fans_count'=>1,
            ));
            $model->trace();
            $model = \HsMysql\HsModel::init('localhost', 9999, 'db_feedstate', 'feed_relation_0', 'idx0');
            $model->insert(array(
                'app_id'=>1,
                'uid'=>$k,
            ));
            $model->trace();
        }

    }

    public function test4Action() {
        $model = \HsMysql\HsModel::init('localhost', 9999, 'db_countstate', 'user_count_0');
        $model->find(1401128538, array('follow_count'), \HsMysql\O::EQ, 0, 10, array(
            array('fans_count', \HsMysql\O::GT, 1),
        ), array(1401128646,1401128538));
        $model->trace();

        exit;
        $model = \HsMysql\HsModel::init('localhost', 9999, 'db_countstate', 'user_count_0');
        $model->decrement(1401128538, array(
            'follow_count'=>1,
        ));
        $model->trace();

        exit;
        $model = \HsMysql\HsModel::init('localhost', 9999, 'db_countstate', 'user_count_0');
        $model->update(1401128538, array(
            'follow_count'=>10,
            'fans_count'=>1
        ), \HsMysql\O::EQ, 0, 1, array(
            array('fans_count', \HsMysql\O::EQ, 1),
        ));
        $model->trace();

        exit;
        $model = \HsMysql\HsModel::init('localhost', 9999, 'db_countstate', 'user_count_0');
        $model->delete(1401128738, \HsMysql\O::EQ, 0, 1, array(
            array('follow_count', \HsMysql\O::EQ, 1),
        ));
        $model->trace();
    }

}