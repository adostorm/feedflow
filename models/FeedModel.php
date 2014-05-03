<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:04
 */

class FeedModel extends \HsMysql\Model {

    public $dbname = 'feed';

    public $tbname = 'feed_content_1';

    public $index = 'PRIMARY';

    public $cache_key = '';

    public function __construct($di) {
        parent::__construct($di, '');
        $this->cache_key = \Util\ReadConfig::get('redis_cache_keys.feed_id_content', $this->getDi());
    }

    public function create($model) {
        $feed_id = $this->insert(array(
            'app_id'=>(int) $model['app_id'],
            'source_id'=>(int) $model['source_id'],
            'object_type'=> $model['object_type'],
            'object_id'=>(int) $model['object_id'],
            'author_id'=>(int) $model['author_id'],
            'centent'=> $model['centent'],
            'create_at'=>(int) $model['create_at'],
        ));

        if($feed_id) {
            $key = sprintf($this->cache_key, $feed_id);
            $redis = \Util\RedisClient::getInstance($this->getDi());
            $redis->set($key, msgpack_pack($model),
                \Util\ReadConfig::get('setting.cache_timeout_alg1', $this->getDi()));
        }

        return $feed_id;
    }

    public function getById($feed_id) {
        $key = sprintf($this->cache_key, $feed_id);

        $redis = \Util\RedisClient::getInstance($this->getDi());
        $result = $redis->get($key);

        if(false === $result) {
            $result = $this->field('id,app_id,source_id,object_type,object_id,author_id,content,create_at')->find($feed_id);
            if($result)  {
                $redis->set($key, msgpack_pack($result),
                    \Util\ReadConfig::get('setting.cache_timeout_alg1', $this->getDi()));
            }
        } else {
            $result = msgpack_unpack($result);
        }

        return $result;
    }


    public function getByUid() {

    }

    public function getByAppId() {

    }
}