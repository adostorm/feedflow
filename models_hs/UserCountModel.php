<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class UserCountModel extends \HsMysql\Model {

    public $dbname = 'userstate';

    public $tbname = 'user_count';

    public $index = 'PRIMARY';

    private $redis = null;

    private $counts_key = '';

    public $cache_big_v_set  = '';

    public $big_v_level = '';

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
                    \Util\ReadConfig::get('setting.cache_timeout_alg1', $this->getDi()));
            }
        } else {
            $counts = msgpack_unpack($counts);
        }

        return $counts;
    }

    public function getCountByField($uid, $field) {
        $count = $this->getCountByUid($uid);
        if(isset($count[$field])) {
            return (int) $count[$field];
        }
        return 0;
    }

    public function updateCount($uid, $field, $num=0, $incr=true) {
        $updates = array();
        if(is_string($field)) {
            $updates[$field] = $num;
        }

        if(true === $incr) {
            $result = $this->increment($uid, $updates);
        } else if(false === $incr) {
            $result = $this->decrement($uid, $updates);
        }

        if(0 === $result) {
            $defalut = array(
                'uid'=>$uid,
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


    public function checkUserCount($uid) {
        return $this->field('uid')->find($uid);
    }

    public function getBigVIds() {
        $results = $this->redis->hgetall($this->cache_big_v_set);
        $rets = array();
        if($results) {
            foreach($results as $id=>$fans_count) {
                $rets[] = $id;
            }
        }
        return $rets;
    }

    public function getBigVList() {
        $results = $this->field('uid,fans_count')->filter(array(
            array('fans_count','>=', $this->big_v_level),
        ))->limit(0, 2000)->find(0, '>');

        $this->redis->pipeline();
        foreach($results as $result) {
            $this->redis->hset($this->cache_big_v_set, $result['uid'], $result['fans_count']);
        }

        $this->redis->exec();

        return $results;
    }

    public function isBigv($uid) {
        $result = $this->redis->hget($this->cache_big_v_set, $uid);
        if(!$result) {
            $results = $this->getCountByUid($uid);
            if(isset($results['fans_count'])) {
                $result = $results['fans_count'];
            }
        }
        $result = intval($result);
        return $result >= $this->big_v_level;
    }

    public function getBigVCount() {
        $result = $this->redis->hlen($this->cache_big_v_set);
        return intval($result);
    }

    public function diffBigV($ids) {
        $tmp = array();
        if(!$ids) {
            $result = $this->redis->hmGet($this->cache_big_v_set, $ids);
            foreach($result as $k=>$v) {
                if($v) {
                    $tmp[] = $k;
                }
            }
        }
        return $tmp;
    }

}