<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-2
 * Time: 下午4:14
 */

class FeedRelationModel extends \HsMysql\Model
{

    public $dbname = 'feedstate';

    public $tbname = 'feed_relation';

    public $index = 'idx0';

    public $partition = array(
        'field'=>'uid',
        'mode'=>'mod',
        'step'=>100
    );

    public $redis = null;

    public $cache_me_appid_id_feeds  = '';

    public function __construct($di) {
        parent::__construct($di, '');
        $this->redis = \Util\RedisClient::getInstance($di);
        $this->cache_me_appid_id_feeds = \Util\ReadConfig::get('redis_cache_keys.me_appid_id_feeds', $di);
    }

    public function create($model)
    {
        $result = $this->insert(array(
            'app_id' => (int) $model['app_id'],
            'uid' => (int) $model['uid'],
            'feed_id' => (int) $model['feed_id'],
            'create_at'=> (int) $model['create_at'],
        ));
        return $result;
    }

    public function getListByUid($app_id, $uid) {
        $key = sprintf($this->cache_me_appid_id_feeds, $app_id, $uid);
        $results = $this->redis->zrange($key, 0, 19);
        return $results;
    }

}