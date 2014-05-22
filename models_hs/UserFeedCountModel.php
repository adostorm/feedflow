<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-21
 * Time: 下午5:04
 */

class UserFeedCountModel extends \HsMysql\Model {

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_countstate';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'user_feed_count';

    /**
     * 主键
     * @var string
     */
    public $index = 'idx0';

    /**
     * Redis 对象
     * @var null|Util\RedisClient
     */
    private $redis = null;

    private $counts_key = '';

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
            \Util\ReadConfig::get('redis_cache_keys.user_appid_id_feedcounts', $di);
    }

    /**
     * 获取 动态数，未读动态数
     * @param $app_id
     * @param $uid
     * @return array
     */
    public function getCountByUid($app_id, $uid)
    {
        $key = sprintf($this->counts_key, $app_id, $uid);
        $counts = $this->redis->get($key);

        if (false === $counts) {
            $counts = $this
                ->field('uid,app_id,feed_count,unread_count')
                ->filter(array(
                    array('app_id', '=', $app_id),
                ))
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
     * @param $app_id
     * @param $uid
     * @param $field
     * @return int
     */
    public function getCountByField($app_id, $uid, $field)
    {
        $count = $this->getCountByUid($app_id, $uid);
        if (isset($count[$field])) {
            return (int)$count[$field];
        }
        return 0;
    }

    /**
     * 更新用户的计数器， 如果在数据库中不存在数据时，则创建，并清除缓存
     * @param $app_id
     * @param $uid
     * @param $field
     * @param int $num
     * @param bool $incr
     * @return array
     */
    public function updateCount($app_id, $uid, $field, $num = 0, $incr = true)
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
                'uid' => (int) $uid,
                'app_id'=> (int) $app_id,
                'feed_count' => 0,
                'unread_count'=>0,
            );

            if (true === $incr) {
                $defalut = array_merge($defalut, $updates);
            }

            $affact = $this->insert($defalut);
            $result = $affact;
        }

        if ($result > 0) {
            $this->redis->setTimeout(sprintf($this->counts_key, $app_id, $uid), 0);
        }

        return $result;
    }

    /**
     * 重置未读动态数为 0
     * @param $app_id
     * @param $uid
     * @return mixed
     */
    public function resetUnReadCount($app_id, $uid) {
        $result = $this->filter(array(
            array('app_id', '=', $app_id),
        ))->update($uid, array(
            'unread_count'=>0,
        ));
        return $result;
    }

}