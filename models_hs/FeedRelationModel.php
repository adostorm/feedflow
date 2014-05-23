<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-2
 * Time: 下午4:14
 */

class FeedRelationModel extends \HsMysql\Model
{

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_feedstate';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'feed_relation';

    /**
     * 主键
     * @var string
     */
    public $index = 'idx0';

    /**
     * Redis 对象
     * @var null|Util\RedisClient
     */
    public $redis = null;

    /**
     * 分表规则
     * @var array
     */
    public $partition = array(
        'field' => 'uid',
        'mode' => 'mod',
        'step' => array(1, 1000000, 2000000, 3000000, 4000000, 5000000,
            6000000, 7000000, 8000000, 9000000, 10000000, 11000000, 12000000,
            13000000, 14000000, 15000000, 16000000, 17000000, 18000000, 19000000,
            20000000, 21000000, 22000000, 23000000, 24000000, 25000000, 26000000,
            27000000, 28000000, 29000000, 30000000, 1000000000),
        'limit' => 399
    );


    /**
     * 初始化DI，Redis
     * @param $di
     */
    public function __construct($di)
    {
        parent::__construct($di, '');
        $this->redis = \Util\RedisClient::getInstance($di);
    }

    /**
     * 创建Feed和用户的关系
     * @param $model
     * @return mixed
     */
    public function create($model)
    {
        $result = $this->insert(array(
            'app_id' => (int)$model['app_id'],
            'uid' => (int)$model['uid'],
            'feed_id' => (int)$model['feed_id'],
            'create_at' => (int)$model['create_at'],
        ));
        return $result;
    }

}