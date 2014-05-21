<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class UserCountModel extends \HsMysql\Model
{

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_countstate';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'user_count';

    /**
     * 主键
     * @var string
     */
    public $index = 'PRIMARY';

    /**
     * Redis 对象
     * @var null|Util\RedisClient
     */
    private $redis = null;

    /**
     * 计数器缓存key
     */
    private $counts_key = '';

    /**
     * 大V缓存Key
     * @var string
     */
    public $cache_big_v_set = '';

    /**
     * 大V Level配置的值，当好友的粉丝大于这个值时，成为大V
     * @var string
     */
    public $big_v_level = '';

    /**
     * 分表规则
     * @var array
     */
    public $partition = array(
        'field' => 'uid',
        'mode' => 'range',
        'step' => array(1, 1000000, 2000000, 3000000, 4000000, 5000000,
            6000000, 7000000, 8000000, 9000000, 10000000, 11000000, 12000000,
            13000000, 14000000, 15000000, 16000000, 17000000, 18000000, 19000000,
            20000000, 21000000, 22000000, 23000000, 24000000, 25000000, 26000000,
            27000000, 28000000, 29000000, 30000000, 1000000000),
        'limit' => 399
    );

    /**
     * 初始化
     * @param $di
     */
    public function __construct($di)
    {
        parent::__construct($di, '');
        $this->redis =
            \Util\RedisClient::getInstance($di);
        $this->counts_key =
            \Util\ReadConfig::get('redis_cache_keys.user_id_counts', $di);
        $this->cache_big_v_set =
            \Util\ReadConfig::get('redis_cache_keys.big_v_set', $this->getDi());
        $this->big_v_level =
            \Util\ReadConfig::get('setting.big_v_level', $this->getDi());
    }

    /**
     * 获取用户的计数器，并写入缓存
     * @param $uid
     * @return array
     */
    public function getCountByUid($uid)
{
    $key = sprintf($this->counts_key, $uid);
    $counts = $this->redis->get($key);

    if (false === $counts) {
        $counts = $this
            ->field('uid,follow_count,fans_count')
            ->find($uid);
        if ($counts) {
            $this->redis->set($key,
                msgpack_pack($counts),
                \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));
        }
    } else {
        $counts = msgpack_unpack($counts);
    }

    return $counts;
}

    /**
     * 根据Key获取计数器里面的数字
     * @param $uid
     * @param $field
     * @return int
     */
    public function getCountByField($uid, $field)
    {
        $count = $this->getCountByUid($uid);
        if (isset($count[$field])) {
            return (int)$count[$field];
        }
        return 0;
    }

    /**
     * 更新用户的计数器， 如果在数据库中不存在数据时，则创建，并清除缓存
     * @param $uid
     * @param $field
     * @param int $num
     * @param bool $incr
     * @return array
     */
    public function updateCount($uid, $field, $num = 0, $incr = true)
    {
        $updates = array();
        if (is_string($field)) {
            $updates[$field] = intval($num);
        } else if(is_array($field)){
            $updates = $field;
        }
        if (true === $incr) {
            $result = $this->increment($uid, $updates);
        } else if (false === $incr) {
            $result = $this->decrement($uid, $updates);
        }

        if (0 === $result) {
            $defalut = array(
                'uid' => (int)$uid,
                'follow_count' => 0,
                'fans_count' => 0,
                'feed_count' => 0,
                'unread_feed_count'=>0,
            );

            if (true === $incr) {
                $defalut = array_merge($defalut, $updates);
            }

            $affact = $this->insert($defalut);
            $result = $affact;
        }

        if ($result > 0) {
            $this->redis->setTimeout(sprintf($this->counts_key, $uid), 0);
        }

        return $result;
    }

    /**
     * 生成大V缓存，根据分表的规则，循环所有的表的计数器，
     *  然后比较 粉丝数 与 setting.big_v_level ，大于0 就成为了大V
     *
     * 这个方法是给 BigvTask 使用
     */
    public function buildBigvCache()
    {
        if (isset($this->partition['step'])
            && is_array($this->partition['step'])) {

            $temps = $this->partition['step'];
            array_pop($temps);
            foreach ($temps as $step) {
                $results = $this->field('uid,fans_count')->filter(array(
                    array('fans_count', '>=', $this->big_v_level),
                ))->limit(0, 2000)->setPartition($step)->find(0, '>');

                if ($results) {
                    $this->redis->pipeline();
                    foreach ($results as $result) {
                        $this->redis->hset($this->cache_big_v_set,
                            $result['uid'], $result['fans_count']);
                    }
                    $this->redis->exec();
                }
            }
        }
    }


    /**
     * 当用户互粉时，判断一次是否大V，并更改大V缓存
     * @param $uid
     * @return bool
     */
    public function setBigv($uid)
    {
        $status = false;
        $fans_count = $this->getCountByField($uid, 'fans_count');
        if ($fans_count > $this->big_v_level) {
            $this->redis->hset($this->cache_big_v_set, $uid, $fans_count);
            $status = true;
        } else {
            $this->redis->hdel($this->cache_big_v_set, $uid);
        }

        return $status;
    }

    /**
     * 批量比较是否大V
     * @param $ids
     * @return array
     */
    public function diffBigv($ids)
    {
        $tmp = array();
        if (!$ids) {
            $results = $this->redis->hmGet($this->cache_big_v_set, $ids);
            if ($results) {
                foreach ($results as $k => $v) {
                    if ($v) {
                        $tmp[] = $k;
                    }
                }
            }
        }
        return $tmp;
    }

}