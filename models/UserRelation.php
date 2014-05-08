<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 上午11:28
 */

class UserRelation extends \Phalcon\Mvc\Model
{

    public $uid = 0;

    public $friend_uid = 0;

    public $status = 0;

    public $create_at = 0;

    public $weight = 0;

    /**
     * @param int $create_at
     */
    public function setCreateAt($create_at)
    {
        $this->create_at = (int)$create_at;
    }

    /**
     * @return int
     */
    public function getCreateAt()
    {
        return (int)$this->create_at;
    }

    /**
     * @param int $friend_uid
     */
    public function setFriendUid($friend_uid)
    {
        $this->friend_uid = (int)$friend_uid;
    }

    /**
     * @return int
     */
    public function getFriendUid()
    {
        return (int)$this->friend_uid;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->status;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = (int)$uid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return (int)$this->uid;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = (int)$weight;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return (int)$this->weight;
    }

    public function initialize()
    {
        $this->setConnectionService('link_userstate');
        $this->redis = \Util\RedisClient::getInstance($this->getDI());
    }

    public function getFollowList($uid, $offset=0, $limit=15)
    {
        $models = UserRelation::find(array(
            'columns'=>'friend_uid',
            "uid=:uid: and status in (0, 1)",
            'order'=>'create_at desc',
            'limit'=>array(
                'number'=>$limit,
                'offset'=>$offset,
            ),
            'bind'=>array(
                'uid'=>$uid,
            ),
        ));

        $results = array();
        if($models->getFirst()) {
            foreach($models as $model) {
                $results[] = $model->friend_uid;
            }
        }

        return $results;
    }


    public function getFansList($uid, $offset=0, $limit=15)
    {
        $models = UserRelation::find(array(
            'columns'=>'friend_uid',
            "uid=:uid: and status in (1,2)",
            'order'=>'create_at desc',
            'limit'=>array(
                'number'=>$limit,
                'offset'=>$offset,
            ),
            'bind'=>array(
                'uid'=>$uid,
            ),
        ));

        $results = array();
        if($models->getFirst()) {
            foreach($models as $model) {
                $results[] = $model->friend_uid;
            }
        }

        return $results;
    }

    public function getInRelationList()
    {

    }
}