<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-2
 * Time: 下午4:14
 */

class FeedRelationModel extends CommonModel
{

    protected $DI = null;

    /**
     * 数据库名称
     * @var string
     */
    protected $dbLink = 'link_db_feedstate';

    /**
     * 表名称
     * @var string
     */
    protected $tbSuffix = 'feed_relation';

    /**
     * 主键
     * @var string
     */
    protected $primary = 'idx0';

    /**
     * Redis 对象
     * @var null|Util\RedisClient
     */
    public $redis = null;


    /**
     * 初始化DI，Redis
     * @param $DI
     */
    public function __construct($DI)
    {
        $this->DI = $DI;
        $this->redis = \Util\RedisClient::getInstance($DI);
    }

    /**
     * 创建Feed和用户的关系
     * @param $data
     * @return mixed
     */
    public function create($data)
    {
        $model = $this->getPartitionModel($data['uid']);
        $result = $model->insert(array(
            'app_id' => (int)$data['app_id'],
            'uid' => (int)$data['uid'],
            'feed_id' => (int)$data['feed_id'],
            'create_at' => (int)$data['create_at'],
        ));
        return $result;
    }

}