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
     * @param int $uid
     * @param int $friend_uid
     * @return int
     *      -98 : 表示 A和B不能自己关注自己
     *      -99 : 表示 A和B不是好友关系，并且数据库不存在记录
     *      -1  : 表示 A和B不是好友关系，但数据库存在记录，但A和B已经同时取消了关注
     *       0  : 表示 A关注了B，但B没有关注A, 此时A是B的粉丝
     *       1  : 表示 A关注了B，B也关注了A，此时A和B互粉，自动升级为好友关系
     *       2  : 表示 B关注了A，但A没有关注B，此时B是A的粉丝
     */
    public function checkRelation($uid, $friend_uid)
    {
        if ($uid == $friend_uid) {
            return -98;
        }
        $this->setIsAssociate(false);
        $result = $this->field('status')->filter(array(
            array('friend_uid', '=', $friend_uid)
        ))->find($uid);
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
        $status = $this->checkRelation($uid, $friend_uid);

        $tempStatus = $status;

        switch ($status) {
            case -99:
                $status = 0;
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
                    'status' => 2,
                    'create_at' => $time,
                ));
                break;

            case -1:
                $status = 0;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 0
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 2
                    ));
                break;

            case 2:
                $status = 1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 1
                    ));
                break;
        }

        if (in_array($tempStatus, array(-99, -1, 2))) {
            $countModel = new UserCountModel($this->getDi());
            $countModel->updateCount($uid, 'follow_count', 1, true);
            $countModel->updateCount($friend_uid, 'fans_count', 1, true);

            $countModel->setBigv($friend_uid);
        }

        return $status;
    }

    public function removeRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($friend_uid, $uid);

        $tempStatus = $status;

        switch ($status) {
            case 0:
                $status = -1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => -1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => -1
                    ));
                break;

            case 1:
                $status = 2;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 2
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 0
                    ));
                break;
        }

        if (in_array($tempStatus, array(0, 1))) {
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

    public function getInRelationList($uid, $friend_uids)
    {
        if(is_string($friend_uids)) {
            $tmp = array();
            $friend_uids = str_replace('，',',', $friend_uids);
            foreach(explode(',', $friend_uids) as $_fuid) {
                $tmp[] = $_fuid;
            }
            $friend_uids = $tmp;
            unset($tmp);
        }
        $results = $this->field('friend_uid,status')->filter(array(
            array('status', '>=', 0),
        ))->in($friend_uids)->setPartition($uid)->find();

        return $results;
    }

    public function transfer($data) {
        if(!$data) {
            return $data;
        }
        $rets = array();
        foreach($data as $row) {
            $rets[$row['friend_uid']] = $row['status'];
        }
        return $rets;
    }
}