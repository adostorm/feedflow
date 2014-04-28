<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:40
 */

use Phalcon\Queue\Beanstalk;

class FeedTask extends \Phalcon\CLI\Task {

    public function test3Action() {
        $config = $this->getDI()->get('config')->{'redis'}->{'link_master0'};
        $redis = new \Redisc\Client($config->{'host'}, $config->{'port'});
        $redis_queue_allfeed_bycount = 'app:%d:feeds';
        $results = $redis->zrange(sprintf($redis_queue_allfeed_bycount, 1), 11, 20);
        var_dump($results);
    }

    public function testAction() {
        $config = $this->getDI()->get('config')->{'redis'}->{'link_master0'};
        $redis = new \Redisc\Client($config->{'host'}, $config->{'port'});
        $redis_queue_allfeed_bycount = 'queue:%d:feed';
        $redis->zadd(sprintf($redis_queue_allfeed_bycount, 1), 1, 'aaaaaaaa');
        $redis->zadd(sprintf($redis_queue_allfeed_bycount, 1), 6, 'bbbbbb');
        $redis->zadd(sprintf($redis_queue_allfeed_bycount, 1), 1, 'ccccc');

        echo PHP_EOL;
    }

    public function test2Action() {
        $proxy = new \HSocket\ModelProxy($this->getDI()->get('config'), 'link_feed_content');
        $model = $proxy->getHandlerSocketModel(\HSocket\Config\IConfig::WRITE_PORT);
        $model->connect(2, 'feed_content_1',
            array('app_id','source_id','object_type','object_id','author_id','centent','create_at','weight'));
        $num = $model->executeInsert(2, array(
            "app_id"=>1,
            "source_id"=>1,
            "object_type"=>6,
            "object_id"=>1024316,
            "author_id"=>6582032,
            "centent"=>"造人前准爸爸也要防辐射",
            "create_at"=>1323504371,
            'weight'=>1,
        ));
        var_dump($num);
    }


    public function runAction() {
        $this->_processQueue();
    }

    private function _processQueue() {
        $bean_queue_allfeed = 'queue:feed';
        $redis_queue_allfeed_byapp = 'app:%d:feeds';

        $config = $this->getDI()->get('config');

        //初始化缓存
        $redisConfig = $config->{'redis'}->{'link_master0'};
        $redis = new \Redisc\Client($redisConfig->{'host'}, $redisConfig->{'port'});

        //初始化队列
        $beanstalkConfig = $config->{"beanstalk"}->{"link_queue0"};
        $queue = new Beanstalk(array(
            'host'=>$beanstalkConfig->{'host'},
            'port'=>$beanstalkConfig->{'port'},
        ));

        $queue->connect();
        $queue->choose($bean_queue_allfeed);
        $queue->watch($bean_queue_allfeed);


        $proxy = new \HSocket\ModelProxy($config, 'link_feed_content');
        $model = $proxy->getHandlerSocketModel(\HSocket\Config\IConfig::WRITE_PORT);
        $model->connect(\HSocket\Model::INSERT, 'feed_content_1', array('app_id','source_id','object_type','object_id','author_id','centent','create_at'));

        while(($job = $queue->peekReady()) !== false) {
            $message = $job->getBody();
            $oldMessage = $message;
            $newMessage = msgpack_unpack($message);
            $num = $model->executeInsert(\HSocket\Model::INSERT, $newMessage);
            if($num > 0) {
                $redis->zadd(sprintf($redis_queue_allfeed_byapp, $newMessage['app_id']), -$newMessage['create_at'], $oldMessage);
                $job->delete();
            }
        }

    }


}