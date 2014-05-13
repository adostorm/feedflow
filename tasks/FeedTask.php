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




//        $queue1 = \Util\BStalkClient::getInstance($di);
//        $queue1->choose($allfeeds);
//        $queue1->watch($allfeeds);
//
//        $queue2 = \Util\BStalkClient::getInstance($di);
//        $queue2->choose($pushfeeds);
//        $queue2->watch($pushfeeds);
    private function _processQueue() {
        $di = $this->getDI();

        $allfeeds = \Util\ReadConfig::get('queue_keys.allfeeds', $di);
        $pushfeeds = \Util\ReadConfig::get('queue_keys.pushfeeds', $di);
        $cache_feeds = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $di);

        $model = new FeedModel($this->getDI());
        $redis = \Util\RedisClient::getInstance($di);


        $config = array('host'=>'127.0.0.1', 'port'=>11980);
        $queue1 = new \Phalcon\Queue\Beanstalk($config);
        $queue1->choose($allfeeds);
        $queue1->watch($allfeeds);

        $queue2 = new \Phalcon\Queue\Beanstalk($config);

        static $isChoose = null;

        while(true) {
            try {
                if(false !== $queue1->peekReady()) {
                    $job = $queue1->reserve();
                    $message = $job->getBody();
                    $oldMessage = $message;
                    var_dump($message);
                    $newMessage = msgpack_unpack($message);
                    $feed_id = $model->create($newMessage);

                    if($feed_id > 0) {
                        $key = sprintf($cache_feeds, $newMessage['app_id']);
                        $redis->zadd($key, -$newMessage['create_at'], $oldMessage);
                        if($redis->zcard($key) > 1000) {
                            $redis->zremrangebyrank($key, 501, -1);
                        }
                        if(!$isChoose) {
                            $queue2->choose(sprintf($pushfeeds, $feed_id%10));
                            $queue2->watch(sprintf($pushfeeds, $feed_id%10));
                        }
                        $queue2->put($newMessage['app_id'].'|'.$newMessage['author_id'].'|'.$feed_id.'|'.$newMessage['create_at']);

                        $job->delete();
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit(1);
            }
            sleep(1);
        }


    }





}