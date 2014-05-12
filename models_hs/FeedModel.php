<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:04
 */

class FeedModel extends \HsMysql\Model {

    public $dbname = 'db_feedcontent';

    public $tbname = 'feed_content';

    public $index = 'PRIMARY';

    public $cache_key = '';

    public $partition = array(
        'field'=>'author_id',
        'mode'=>'range',
        'step'=>array(1,100000,200000,300000,400000,500000,
            600000,700000,800000,900000,1000000,1100000,1200000,
            1300000,1400000,1500000,1600000,1700000,1800000,1900000,
            2000000,1000000000),
        'limit'=>399
    );

    public function __construct($di) {
        parent::__construct($di, '');
        $this->cache_key =
            \Util\ReadConfig::get('redis_cache_keys.feed_id_content', $this->getDi());
    }

    public function create($data) {
        $feedIndexModel = new FeedIndexModel($this->getDi());
        $feed_id = $feedIndexModel->create();

        $isOk = $this->insert(array(
            'feed_id'=> $feed_id,
            'app_id'=>(int) $data['app_id'],
            'source_id'=>(int) $data['source_id'],
            'object_type'=> (int)$data['object_type'],
            'object_id'=>(int) $data['object_id'],
            'author_id'=>(int) $data['author_id'],
            'author'=>strval($data['author']),
            'content'=> strval($data['content']),
            'create_at'=>(int) $data['create_at'],
            'attachment'=>strval($data['attachment']),
            'extends'=>strval($data['extends']),
        ));

        if($isOk) {
            $count = new UserCountModel($this->getDi());
            $count->updateCount($data['author_id'], 'feed_count', 1, true);

            $key = sprintf($this->cache_key, $feed_id);
            $redis = \Util\RedisClient::getInstance($this->getDi());
            $redis->set($key, msgpack_pack($data),
                \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));

            return $feed_id;
        }

        return false;
    }

    public function getById($feed_id) {
        $key = sprintf($this->cache_key, $feed_id);

        $redis = \Util\RedisClient::getInstance($this->getDi());
        $result = $redis->get($key);

        if(false === $result) {
            $fields = array('id','app_id','source_id',
                            'object_type','object_id',
                            'author_id', 'author', 'content',
                            'create_at','attachment','extends');
            $result = $this->field($fields)->find($feed_id);
            if($result)  {
                $redis->set($key, msgpack_pack($result),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));
            }
        } else {
            $result = msgpack_unpack($result);
        }

        return $result;
    }

    public function push($app_id, $uid, $feed_id, $time) {
        $key = \Util\ReadConfig::get('queue_keys.pushfeeds', $this->getDi());
        $beans = \Util\BStalkClient::getInstance($this->getDi());
        $beans->choose(sprintf($key, $feed_id%10));
        $beans->watch(sprintf($key, $feed_id%10));
        $beans->put($app_id.'|'.$uid.'|'.$feed_id.'|'.$time);
        $beans->disconnect();
    }
}