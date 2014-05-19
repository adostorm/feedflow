<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:40
 */

class FeedTask extends \Phalcon\CLI\Task
{

    private $k1 = '';
    private $k2 = '';
    private $q1 = null;
    private $q2 = null;
    private $redis = null;
    private $cache_key = '';

    /**
     * php cli.php Feed run
     */
    public function runAction()
    {
        $this->_init();
        $this->_processQueue();
    }

    private function _init()
    {
        $di = $this->getDI();
        $this->k1 = \Util\ReadConfig::get('queue_keys.allfeeds', $di);
        $this->k2 = \Util\ReadConfig::get('queue_keys.pushfeeds', $di);
        $this->q1 = \Util\BStalkClient::getInstance($di, 'link_queue0');
        $this->q2 = \Util\BStalkClient::getInstance($di, 'link_queue1');
        $this->redis = \Util\RedisClient::getInstance($di);
        $this->cache_key = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $di);
    }

    private function _processQueue()
    {
        $this->q1->choose($this->k1);
        $this->q1->watch($this->k1);
        $model = new FeedModel($this->getDI());

        try {
            while (1) {
                while (false !== $this->q1->peekReady()) {
                    $job = $this->q1->reserve();

                    $old = $job->getBody();
                    $new = msgpack_unpack($old);

                    var_dump($new);
                    $feed_id = $model->create($new);

                    if ($feed_id) {
                        $this->q2->choose(sprintf($this->k2, 1/*$feed_id % 10*/));
                        $this->q2->put(sprintf('%d|%d|%d|%d'
                            , $new['app_id'], $new['author_id'], $feed_id, $new['create_at']));

                        $_key = sprintf($this->cache_key, $new['app_id']);
                        $this->redis->zadd($_key, -$new['create_at'], $old);
                        if ($this->redis->zcard($_key) > 1000) {
                            $this->redis->zremrangebyrank($_key, 501, -1);
                        }

                        $job->delete();
                    } else {
                        $job->bury();
                    }
                }
                sleep(3);
            }
        } catch (\Phalcon\Exception $e) {
            if ($this->q1) {
                $this->q1->disconnect();
            }
            if ($this->q2) {
                $this->q2->disconnect();
            }
            echo $e->getMessage();
            exit(1);
        }
    }


}