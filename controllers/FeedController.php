<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:46
 */

use Redisc\Client as Redis;

class FeedController extends CController {

    public function getFeedByAppId() {

        $pageDefault = 1;
        $countDefault = 10;

        $app_id = $this->request->get('app_id');
        $page = $this->request->get('page', 'intval', $pageDefault);
        $count = $this->request->get('count', 'intval', $countDefault);

        $page = $page > 0 ? $page : $pageDefault;
        $count = $count > 0 && $count <= 50 ? $count : $countDefault;

        $limit = ($page - 1) * $count;
        $offset = $count * $page;

        $config = $this->getDI()->get('config');
        $redisConfig = \Util\ReadConfig::get('redis.link_master0', $config);
        $cache_key_appfeed = \Util\ReadConfig::get('appfeed', $config);

        $redis = new Redis(\Util\ReadConfig::get('host', $redisConfig),
            \Util\ReadConfig::get('port', $redisConfig));

        $results = $redis->zrange(sprint($cache_key_appfeed, $app_id), $limit, $offset);

        var_dump($results);
    }

    public function getFeedByUid() {

    }

    public function create() {
        $mode = $this->request->getPost('mode');
        $msg = $this->request->getPost('msg');

        $queue = new \Phalcon\Queue\Beanstalk(array(
            'host'=>'127.0.0.1',
            'port'=>11307
        ));
        $queue->connect();
        $queue->choose('bean:queue:feed');

        if($mode=='multi') {
            $msgArray = msgpack_unpack($msg);
            foreach($msgArray as $row) {
                $queue->put(msgpack_pack($row));
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