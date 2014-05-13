<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午11:22
 */

class UserRelationModel extends \HsMysql\Model
{

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_userstate';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'user_relation';

    /**
     * 主键
     * @var string
     */
    public $index = 'idx0';

    /**
     * 主键
     * @var string
     */
    public $redis = null;

    /**
     * 大V缓存Key
     * @var string
     */
    public $cache_big_v_set = '';

    /**
     * 分表规则
     * @var array
     */
    public $partition = array(
        'field' => 'uid',
        'mode' => 'range',
        'step' => array(1, 100000, 200000, 300000, 400000, 500000,
            600000, 700000, 800000, 900000, 1000000, 1100000, 1200000,
            1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000,
            2000000, 1000000000),
        'limit' => 399
    );

    /**
     * 初始化
     * @param $di
     */
    public function __construct($di)
    {
        parent::__construct($di, '');
        $this->redis = \Util\RedisClient::getInstance($this->getDI());
        $this->cache_big_v_set =
            \Util\ReadConfig::get('redis_cache_keys.big_v_set', $this->getDi());
    }

    /**
     * 检查好友关系， 直接查库
     * 根据对方ID与用户自己ID
     * @param $friend_uid
     * @param $uid
     * @return int
     */
    public function checkRelation($friend_uid, $uid)
    {
        if ($friend_uid == $uid) {
            return -98;
        }
        $this->setIsAssociate(false);
        $result = $this->field('status')->filter(array(
            array('friend_uid', '=', $uid)
        ))->find($friend_uid);
        return isset($result[0]) ? intval($result[0]) : -99;
    }

    /**
     * 创建好友关系
     *      1,
     * @param $uid
     * @param $friend_uid
     * @return int
     */
    public function createRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($friend_uid, $uid);

        $tempStatus = $status;

        switch ($status) {
            case -99:
                $status = 2;
                $time = time();
                $this->insert(array(
                    'uid' => $uid,
                    'friend_uid' => $friend_uid,
                    'status' => 0,
                    'create_at' => $time,
                ));
                $this->insert(array(
                    'uid' => $friend_uid,
                    'friend_uid' => $uid,
                    'status' => $status,
                    'create_at' => $time,
                ));
                break;

            case -1:
                $status = 2;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 0
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => $status
                    ));
                break;

            case 0:
                $status = 1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => $status
                    ));
                break;
        }

        if (in_array($tempStatus, array(-99, -1, 0))) {
            $countModel = new UserCountModel($this->getDi());
            $countModel->updateCount($uid, 'follow_count', 1);
            $countModel->updateCount($friend_uid, 'fans_count', 1);

            $countModel->setBigv($friend_uid);
        }

        return $status;
    }

    public function removeRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($friend_uid, $uid);

        $tempStatus = $status;

        switch ($status) {
            case 2:
                $status = -1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => -1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => $status
                    ));
                break;

            case 1:
                $status = 0;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 2
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => $status
                    ));
                break;

            case -1:
                $status = -99;
                break;
        }

        if (in_array($tempStatus, array(2, 1))) {
            $countModel = new UserCountModel($this->getDi());
            $countModel->updateCount($uid, 'follow_count', 1, false);
            $countModel->updateCount($friend_uid, 'fans_count', 1, false);

            $countModel->setBigv($friend_uid);
        }

        return $status;
    }

    public function getFollowList($uid, $offset = 0, $limit = 15)
    {
        $results = $this->field('friend_uid')->filter(array(
            array('status', '>=', 0),
            array('status', '<=', 1),
        ))->limit($offset, $limit)->find($uid);
        return $results;
    }

    public function getFansList($uid, $offset = 0, $limit = 15)
    {
        $results = $this->field('friend_uid')->filter(array(
            array('status', '>', 0),
        ))->limit($offset, $limit)->find($uid);
        return $results;
    }

}