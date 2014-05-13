<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class UserCountModel extends \HsMysql\Model {

    public $dbname = 'db_countstate';

    public $tbname = 'user_count';

    public $index = 'PRIMARY';

    private $redis = null;

    private $counts_key = '';

    public $cache_big_v_set  = '';

    public $big_v_level = '';

    public $partition = array(
        'field'=>'uid',
        'mode'=>'range',
        'step'=>array(1,1000000,2000000,3000000,4000000,5000000,
            6000000,7000000,8000000,9000000,10000000,11000000,12000000,
            13000000,14000000,15000000,16000000,17000000,1000000000),
        'limit'=>399
    );

    public function __construct($di) {
        parent::__construct($di, '');
        $this->redis = \Util\RedisClient::getInstance($di);
        $this->counts_key = \Util\ReadConfig::get('redis_cache_keys.user_id_counts', $di);
        $this->cache_big_v_set = \Util\ReadConfig::get('redis_cache_keys.big_v_set', $this->getDi());
        $this->big_v_level = \Util\ReadConfig::get('setting.big_v_level', $this->getDi());
    }

    public function getCountByUid($uid) {
        $key = sprintf($this->counts_key, $uid);
        $counts = $this->redis->get($key);

        if(false === $counts) {
            $counts = $this->field('uid,follow_count,fans_count,feed_count')->find($uid);
            if($counts) {
                $this->redis->set($key, msgpack_pack($counts),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));
            }
        } else {
            $counts = msgpack_unpack($counts);
        }

        return $counts;
    }

    public function getCountByField($uid, $field) {
        $count = $this->getCountByUid($uid);
        if(isset($count[0]) && isset($count[0][$field])) {
            return (int) $count[0][$field];
        }
        return 0;
    }

    public function updateCount($uid, $field, $num=0, $incr=true) {
        $updates = array();
        if(is_string($field)) {
            $updates[$field] = intval($num);
        }
        if(true === $incr) {
            $result = $this->increment($uid, $updates);
        } else if(false === $incr) {
            $result = $this->decrement($uid, $updates);
        }

        if(0 === $result) {
            $defalut = array(
                'uid'=> (int) $uid,
                'follow_count'=>0,
                'fans_count'=>0,
                'feed_count'=>0,
            );

            if(true === $incr) {
                $defalut = array_merge($defalut, $updates);
            }

            $affact = $this->insert($defalut);
            $result = $affact;
        }

        if($result > 0) {
            $this->redis->setTimeout(sprintf($this->counts_key, $uid), 0);
        }

        return $result;
    }

    public function buildBigvCache() {
        if(isset($this->partition['step']) && is_array($this->partition['step'])) {
            $temps = $this->partition['step'];
            array_pop($temps);
            foreach($temps as $step) {
                $results = $this->field('uid,fans_count')->filter(array(
                    array('fans_count','>=', $this->big_v_level),
                ))->limit(0, 2000)->setPartition($step)->find(0, '>');

                if($results) {
                    $this->redis->pipeline();
                    foreach($results as $result) {
                        $this->redis->hset($this->cache_big_v_set, $result['uid'], $result['fans_count']);
                    }
                    $this->redis->exec();
                }
            }
        }
    }

    public function setBigv($uid) {
        $status = false;
        $fans_count = $this->getCountByField($uid, 'fans_count');
        if($fans_count > $this->big_v_level) {
            $this->redis->hset($this->cache_big_v_set, $uid, $fans_count);
            $status = true;
        } else {
            $this->redis->hdel($this->cache_big_v_set, $uid);
        }

        return $status;
    }

    public function diffBigv($ids) {
        $tmp = array();
        if(!$ids) {
            $results = $this->redis->hmGet($this->cache_big_v_set, $ids);
            if($results) {
                foreach($results as $k=>$v) {
                    if($v) {
                        $tmp[] = $k;
                    }
                }
            }
        }
        return $tmp;
    }

}