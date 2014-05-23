<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-8
 * Time: 下午12:12
 */

class UserFeed extends AdvModel
{

    /**
     * Feed系统feed的ID
     * @var int
     */
    public $feed_id = 0;

    /**
     * 应用ID
     * @var int
     */
    public $app_id = 0;

    /**
     * 用户的ID
     * @var int
     */
    public $uid = 0;

    /**
     * 创建时间
     * @var int
     */
    public $create_at = 0;

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_userfeed';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'user_feed';

    /**
     * 分库分表规则
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
     * @param int $app_id
     */
    public function setAppId($app_id)
    {
        $this->app_id = $app_id;
    }

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * @param int $create_at
     */
    public function setCreateAt($create_at)
    {
        $this->create_at = $create_at;
    }

    /**
     * @return int
     */
    public function getCreateAt()
    {
        return $this->create_at;
    }

    /**
     * @param int $feed_id
     */
    public function setFeedId($feed_id)
    {
        $this->feed_id = $feed_id;
    }

    /**
     * @return int
     */
    public function getFeedId()
    {
        return $this->feed_id;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }


    /**
     * 查询用户自己的动态
     *      拉：大V的动态
     * @param $app_id
     * @param $uid
     * @param array $extends
     * @return array
     */
    public function getFeedListByAppIdAndUid($app_id, $uid, $extends = array())
    {
        $this->init($uid);
        $default = array(
            'offset' => 0,
            'limit' => 15,
            'timeline' => 0,
            'fields' => 'app_id,uid,feed_id,create_at',
            'order' => 'create_at desc'
        );
        $conditions = array_merge($default, $extends);

        $results = $this->find(array(
            'app_id=:app_id: and uid=:uid: and create_at>=:create_at:',
            'columns' => $conditions['fields'],
            'order' => $conditions['order'],
            'limit' => array(
                'offset' => $conditions['offset'],
                'number' => $conditions['limit'],
            ),
            'bind' => array(
                'uid' => $uid,
                'app_id' => $app_id,
                'create_at' => $conditions['timeline'],
            ),
        ));

        $rets = array();

        if ($results->getFirst()) {
            $feedHsModel = new FeedModel($this->getDI());
            foreach ($results as $result) {
                $rets[] = $feedHsModel->getById($result->uid, $result->feed_id);
            }
        }

        return $rets;
    }

}