<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class UserCountModel extends CommonModel
{

    protected $DI = null;

    protected $dbLink = 'link_db_countstate';

    protected $tbSuffix = 'user_count';

    protected $primary = 'PRIMARY';

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
    protected $cache_big_v_set = '';

    /**
     * 大V Level配置的值，当好友的粉丝大于这个值时，成为大V
     * @var string
     */
    protected $big_v_level = '';

    /**
     * 初始化
     * @param $DI
     */
    public function __construct($DI)
    {
        $this->DI = $DI;
        $this->redis =
            \Util\RedisClient::getInstance($DI);
        $this->counts_key =
            \Util\ReadConfig::get('redis_cache_keys.user_id_counts', $DI);
        $this->cache_big_v_set =
            \Util\ReadConfig::get('redis_cache_keys.big_v_set', $DI);
        $this->big_v_level =
            \Util\ReadConfig::get('setting.big_v_level', $DI);
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
            $model = $this->getPartitionModel($uid);
            $counts = $model->setField('uid,follow_count,fans_count')->find($uid);
            if ($counts) {
                $counts = $counts[0];
                $this->redis->set($key,
                    msgpack_pack($counts[0]),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->DI));
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

        $model = $this->getPartitionModel($uid);

        if (true === $incr) {
            $result = $model->increment($uid, $updates);
        } else if (false === $incr) {
            $result = $model->decrement($uid, $updates);
        }

        if (0 === $result) {
            $default = array(
                'uid' => (int) $uid,
                'follow_count' => 0,
                'fans_count' => 0,
            );

            if (true === $incr) {
                $default = array_merge($default, $updates);
            }

            $affect = $model->insert($default);
            $result = $affect;
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
                $model = $this->getPartitionModel($step);
                $results = $model->setField('uid,fans_count')->setFilter(array(
                    array('fans_count', '>=', $this->big_v_level),
                ))->setLimit(0, 2000)->find(0, '>');

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