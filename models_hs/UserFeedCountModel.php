<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-21
 * Time: 下午5:04
 */

class UserFeedCountModel extends CommonModel
{

    protected $DI = null;

    /**
     * 数据库名称
     * @var string
     */
    protected $dbLink = 'link_db_countstate';

    /**
     * 表名称
     * @var string
     */
    protected $tbSuffix = 'user_feed_count';

    /**
     * 主键
     * @var string
     */
    protected $primary = 'idx0';

    /**
     * Redis 对象
     * @var null|Util\RedisClient
     */
    private $redis = null;

    private $counts_key = '';

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
            \Util\ReadConfig::get('redis_cache_keys.user_appid_id_feedcounts', $DI);
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
            $model = $this->getPartitionModel($uid);
            $counts = $model
                ->setField('uid,app_id,feed_count,unread_count')
                ->setFilter(array(
                    array('app_id', '=', $app_id),
                ))->find($uid);

            if ($counts) {
                $counts = $counts[0];
                $this->redis->set($key,
                    msgpack_pack($counts),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->DI));
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
        } else if (is_array($field)) {
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
                'uid' => (int)$uid,
                'app_id' => (int)$app_id,
                'feed_count' => 0,
                'unread_count' => 0,
            );

            if (true === $incr) {
                $default = array_merge($default, $updates);
            }

            $affect = $model->insert($default);
            $result = $affect;
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
    public function resetUnReadCount($app_id, $uid)
    {
        $model = $this->getPartitionModel($uid);
        $result = $model->setFilter(array(
            array('app_id', '=', $app_id),
        ))->update($uid, array(
                'unread_count' => 0,
            ));
        return $result;
    }

}