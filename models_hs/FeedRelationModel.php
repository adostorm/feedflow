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
        'field'=>'uid',
        'mode'=>'mod',
        'step'=>array(1,100000,200000,300000,400000,500000,
            600000,700000,800000,900000,1000000,1100000,1200000,
            1300000,1400000,1500000,1600000,1700000,1800000,1900000,
            2000000,1000000000),
        'limit'=>399
    );


    /**
     * 初始化DI，Redis
     * @param $di
     */
    public function __construct($di) {
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
            'app_id' => (int) $model['app_id'],
            'uid' => (int) $model['uid'],
            'feed_id' => (int) $model['feed_id'],
            'create_at'=> (int) $model['create_at'],
        ));
        return $result;
    }

}