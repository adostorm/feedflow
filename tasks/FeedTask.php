<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:40
 */

class FeedTask extends \Phalcon\CLI\Task {

    public function runAction() {
        $this->_processQueue();
    }

    private function _processQueue() {
        $di = $this->getDI();

        $queue_key = \Util\ReadConfig::get('queue_keys.allfeeds', $di);
        $queue = \Util\BStalkClient::getInstance($di);
        $queue->choose($queue_key);
        $queue->watch($queue_key);

        $redis = \Util\RedisClient::getInstance($di);
        $cache_app_id_feeds = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $di);

        $model = new FeedModel($this->getDI());

        while(true) {
            if(false !== $queue->peekReady()) {
                $job = $queue->reserve();
                $message = $job->getBody();
                $oldMessage = $message;
                var_dump($message);
                $newMessage = msgpack_unpack($message);
                $feed_id = $model->create($newMessage);

                if($feed_id > 0) {
                    $key = sprintf($cache_app_id_feeds, $newMessage['app_id']);
                    $job->delete();
                    $redis->zadd($key, -$newMessage['create_at'], $oldMessage);

                    $model->push($newMessage['app_id'],
                        $newMessage['author_id'],
                        $feed_id,
                        $newMessage['create_at']);

                    if($redis->zcard($key) > 1000) {
                        $redis->zremrangebyrank($key, 501, -1);
                    }

                    $job->delete();

                }

            }

            exit;
//            sleep(1);
        }


    }





}