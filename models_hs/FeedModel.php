<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:04
 */

class FeedModel extends \HsMysql\Model {

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_feedcontent';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'feed_content';

    /**
     * 主键
     * @var string
     */
    public $index = 'PRIMARY';

    /**
     * redis Feed内容缓存Key
     * @var string
     */
    public $cache_key = '';

    /**
     * 分表规则
     * @var array
     */
    public $partition = array(
        'field'=>'author_id',
        'mode'=>'range',
        'step'=>array(1,100000,200000,300000,400000,500000,
            600000,700000,800000,900000,1000000,1100000,1200000,
            1300000,1400000,1500000,1600000,1700000,1800000,1900000,
            2000000,1000000000),
        'limit'=>399
    );

    /**
     * 构造函数
     *      初始化DI，缓存Key
     * @param $di
     */
    public function __construct($di) {
        parent::__construct($di, '');
        $this->cache_key =
            \Util\ReadConfig::get('redis_cache_keys.feed_id_content', $this->getDi());
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
    public function create($data) {
        $feedIndexModel = new FeedIndexModel($this->getDi());
        $feed_id = $feedIndexModel->create();

        if($feed_id) {
            $userFeedModel = new UserFeedModel($this->getDi());
            $isSuccess = $userFeedModel->create(array(
                'app_id'=>$data['app_id'],
                'uid'=>$data['author_id'],
                'feed_id'=>$feed_id,
                'create_at'=>$data['create_at'],
            ));

            $isOk = $this->insert(array(
                'feed_id'=> $feed_id,
                'app_id'=>(int) $data['app_id'],
                'source_id'=>(int) $data['source_id'],
                'object_type'=> (int)$data['object_type'],
                'object_id'=>(int) $data['object_id'],
                'author_id'=>(int) $data['author_id'],
                'author'=>strval($data['author']),
                'content'=> strval($data['content']),
                'create_at'=>(int) $data['create_at'],
                'attachment'=>strval($data['attachment']),
                'extends'=>strval($data['extends']),
            ));

            if($isOk&&$isSuccess) {
                $count = new UserCountModel($this->getDi());
                $count->updateCount($data['author_id'], 'feed_count', 1, true);

                $key = sprintf($this->cache_key, $feed_id);
                $redis = \Util\RedisClient::getInstance($this->getDi());
                $redis->set($key, msgpack_pack($data),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));

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
     * @param $feed_id
     * @return array
     */
    public function getById($feed_id) {
        $key = sprintf($this->cache_key, $feed_id);

        $redis = \Util\RedisClient::getInstance($this->getDi());
        $result = $redis->get($key);

        if(false === $result) {
            $fields = array('feed_id','app_id','source_id',
                            'object_type','object_id',
                            'author_id', 'author', 'content',
                            'create_at','attachment','extends');
            $result = $this->field($fields)->find($feed_id);

            if($result)  {
                $redis->set($key, msgpack_pack($result),
                    \Util\ReadConfig::get('setting.cache_timeout_t1', $this->getDi()));
            }
        } else {
            $result = msgpack_unpack($result);
        }

        return $result;
    }

}