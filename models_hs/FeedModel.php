<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:04
 */

class FeedModel extends CommonModel
{

    protected $DI = null;

    protected $dbLink = 'link_db_feedcontent';

    protected $tbSuffix = 'feed_content';

    protected $primary = 'PRIMARY';

    protected $cache_key = '';

    /**
     * 构造函数
     *      初始化DI，缓存Key
     * @param $DI
     */
    public function __construct($DI) {
        $this->DI = $DI;
        $this->cache_key =
            \Util\ReadConfig::get('redis_cache_keys.feed_id_content', $DI);
    }

    /**
     * 创建一条Feed内容
     *      1, 生成Feed索引
     *      2, 生成用户索引
     *      3, 写入Feed内容
     *      4, 更新用户的Feed Count
     *      5, 写入Redis缓存
     * @param $data
     * @return bool
     */
    public function create($data)
    {
        $feedIndexModel = new FeedIndexModel($this->DI);
        $feed_id = $feedIndexModel->create();

        if ($feed_id) {
            $userFeedModel = new UserFeedModel($this->DI);
            $isSuccess = $userFeedModel->create(array(
                'app_id' => $data['app_id'],
                'uid' => $data['author_id'],
                'feed_id' => $feed_id,
                'create_at' => $data['create_at'],
            ));

            $model = $this->getPartitionModel($data['author_id']);
            $isOk = $model->insert(array(
                'feed_id' => (int)$feed_id,
                'app_id' => (int)$data['app_id'],
                'source_id' => (int)$data['source_id'],
                'object_type' => (int)$data['object_type'],
                'object_id' => (int)$data['object_id'],
                'author_id' => (int)$data['author_id'],
                'author' => strval($data['author']),
                'content' => strval($data['content']),
                'create_at' => (int)$data['create_at'],
                'attachment' => strval($data['attachment']),
                'extends' => msgpack_pack($data['extends']),
            ));

            if ($isOk && $isSuccess) {
                $userFeedCountModel = new UserFeedCountModel($this->DI);
                $userFeedCountModel->updateCount($data['app_id'], $data['author_id'], array(
                    'feed_count' => 1,
                ), 0, true);

                $key = sprintf($this->cache_key, $feed_id);
                $redis = \Util\RedisClient::getInstance($this->DI);
                $redis->set($key, msgpack_pack($data),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->DI));

                return $feed_id;
            }
        }

        return false;
    }

    /**
     * 取得一条Feed的内容
     *      1, 先取缓存
     *      2, 如果缓存过期，则从数据库取
     *      3, 从数据库取出数据后，整个数据使用msgpack_pack压缩后再写入Redis缓存
     * @param $uid
     * @param $feed_id
     * @return array
     */
    public function getById($uid, $feed_id)
    {
        $key = sprintf($this->cache_key, $feed_id);

        $redis = \Util\RedisClient::getInstance($this->DI);
        $result = $redis->get($key);

        if (false === $result) {
            $fields = array('feed_id', 'app_id', 'source_id',
                'object_type', 'object_id',
                'author_id', 'author', 'content',
                'create_at', 'attachment', 'extends');
            $model = $this->getPartitionModel($uid);
            $result = $model->setField($fields)->find($feed_id);

            if ($result) {
                $result = $result[0];

                $result['extends'] = msgpack_unpack($result['extends']);
                $redis->set($key, msgpack_pack($result),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->DI));
            }
        } else {
            $result = msgpack_unpack($result);
        }

        return $result;
    }

}