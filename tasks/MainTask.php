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

}