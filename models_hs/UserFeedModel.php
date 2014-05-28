<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-12
 * Time: 下午2:07
 */

class UserFeedModel extends CommonModel
{

    protected $DI = null;

    /**
     * 数据库名称
     * @var string
     */
    protected $dbLink = 'link_db_userfeed';

    /**
     * 表名称
     * @var string
     */
    protected $tbSuffix = 'user_feed';

    /**
     * 主键
     * @var string
     */
    protected $primary = 'idx0';

    /**
     * 初始化
     * @param $DI
     */
    public function __construct($DI)
    {
        $this->DI = $DI;
    }

    /**
     * 创建用户的Feed索引
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