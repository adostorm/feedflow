<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 上午11:28
 */

class UserRelation extends AdvModel
{

    /**
     * 用户的ID
     * @var int
     */
    public $uid = 0;

    /**
     * 用户的ID
     * @var int
     */
    public $friend_uid = 0;

    /**
     * 好友关系的状态
     * @var int
     */
    public $status = 0;

    /**
     * 创建时间
     * @var int
     */
    public $create_at = 0;

    /**
     * 权重
     * @var int
     */
    public $weight = 0;

    /**
     * 数据库名称
     * @var string
     */
    public $dbLink = 'link_db_userstate';

    /**
     * 表名称
     * @var string
     */
    public $tbSuffix  = 'user_relation';

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

    /**
     * 好友列表
     * @param $uid
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getFollowList($uid, $offset = 0, $limit = 15)
    {
        return $this->_common($uid, array(0, 1), $offset, $limit);
    }


    /**
     * 粉丝列表
     * @param $uid
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getFansList($uid, $offset = 0, $limit = 15)
    {
        return $this->_common($uid, array(1, 2), $offset, $limit);
    }

    private function _common($uid, $status=array(), $offset = 0, $limit = 15) {
        $this->init($uid);
        $models = $this->find(array(
            'uid=:uid: and status in ('.implode(',',$status).')',
            'columns' => 'friend_uid, status, create_at',
            'order' => 'create_at desc',
            'limit' => array(
                'number' => $limit,
                'offset' => $offset,
            ),
            'bind' => array(
                'uid' => $uid,
            ),
        ));

        $results = array();
        if ($models->getFirst()) {
            foreach ($models as $model) {
                $results[] = array(
                    'uid'=>(int) $model->friend_uid,
                    'relation'=>(int) $model->status,
                    'create_at'=>(int) $model->create_at,
                );
            }
        }

        return $results;
    }


}