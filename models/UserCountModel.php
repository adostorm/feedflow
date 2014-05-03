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

    public function __construct($di) {
        parent::__construct($di, '');
        $this->redis = \Util\RedisClient::getInstance($di);
        $this->counts_key = \Util\ReadConfig::get('redis_cache_keys.user_id_counts', $di);
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


}