<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:46
 */

class FeedController extends CController {

    public function getFeedListByAppId() {

        $app_id = $this->request->get('app_id', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $limit = ($page - 1) * $count;
        $offset = $count * $page - 1;

        $di = $this->getDI();
        $redis = \Util\RedisClient::getInstance($di);
        $zaddKey = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $di);
        $results = $redis->zrange(sprintf($zaddKey, $app_id), $limit, $offset);

        if(!$results) {
            $model = new FeedModel($di);
//            $model->find();
        }

        $feedList = array();
        if($results) {
            foreach($results as $result) {
                $feedList[] = msgpack_unpack($result);
            }
            unset($results);
        }

        var_dump($feedList);
    }

    public function getFeedListByUid() {

    }

    public function create() {
        $mode = $this->request->getPost('mode');
        $msg = $this->request->getPost('msg');

        $queue = \Util\BStalkClient::getInstance($this->getDI());
        $queue->choose(\Util\ReadConfig::get('queue_keys.allfeeds', $this->getDI()));

        if($mode=='multi') {
            $msgArray = msgpack_unpack($msg);
            if(is_array($msgArray)) {
                foreach($msgArray as $row) {
                    $queue->put(msgpack_pack($row));
                }
            }
        } else {
            $queue->put($msg);
        }

        $queue->disconnect();

        echo json_encode(array(
            'status'=>1,
            'msg'=>'Ok.'
        ));
    }

}