<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-8
 * Time: 下午12:12
 */

class UserFeed extends \Phalcon\Mvc\Model
{

    public $feed_id = 0;

    public $app_id = 0;

    public $uid = 0;

    public $create_at = 0;

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


    public function getListByAppIdAndUid($app_id, $uid, $extends = array())
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
            'app_id=:app_id: and uid=:uid: and create_at>=:timeline:',
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
                $rets[] =  $feedHsModel->getById($result->feed_id);
            }
        }

        return $rets;
    }

}