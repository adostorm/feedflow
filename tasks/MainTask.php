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
//        $h = new \HandlerSocket('localhost', 9999);
//
//        $h->openIndex(1, 'db_countstate', 'user_count_0', 'PRIMARY', 'uid,follow_count,fans_count');
//        $result = $h->executeInsert(1, array(231312,1,1));
//        var_dump($result, $h->getError());
//
//        $h->openIndex(2, 'db_countstate', 'user_count_2', 'PRIMARY', 'uid,follow_count,fans_count');
//        $result = $h->executeInsert(2, array(2313128,1,1));
//        var_dump($result, $h->getError());
//        $h->openIndex(3, 'db_countstate', 'user_count_30', 'PRIMARY', 'uid,follow_count,fans_count');
//        $result = $h->executeInsert(3, array(231312899,1,1));
//        var_dump($result, $h->getError());
//        exit;


        $uid = 140112;
        $m = new TestModel($this->getDI());

//        $m->getCountByUid($uid);
//        $m->getCountByUid(1313123);
        for($i=0;$i<100;$i++) {
            $m->addCount(230312+$i);
            $m->addCount(232314+$i);
            $m->addCount(234315+$i);
            $m->addCount(2313128+$i);
            $m->addCount(231312899+$i);
        }

//        $m->updateCount(13131288);
//        $m->incrCount(13131288);
    }

    public function test5Action() {
        $m = new TestModel($this->getDI());
        for($i=0;$i<100;$i++) {
            $m->addCount(236312+$i);
            $m->addCount(238314+$i);
            $m->addCount(244315+$i);
            $m->addCount(2323128+$i);
            $m->addCount(231312899+$i);
        }
    }

    public function test6Action() {
        $m = new UserRelationModel($this->getDI());
        $m->getFansList(1231, 0, 15);


        exit;
        var_dump(UserCountModel::$partition);

        exit;
        $m = new FeedIndexModel($this->getDI());
        $m->create();

        exit;
        $m = new UserCountModel($this->getDI());
        $result = $m->getCountByUid(1);
        $m->updateCount(1, 'follow_count', 1);
        exit;
        $m->getCountByUid(2);
        $m->getCountByUid(3);

//        $m->updateCount(1, 'follow_count', 1);
    }

}