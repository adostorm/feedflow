<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午11:22
 */

class UserRelationModel extends CommonModel
{

    protected $DI = null;

    /**
     * 数据库名称
     * @var string
     */
    protected $dbLink = 'link_db_userstate';

    /**
     * 表名称
     * @var string
     */
    protected $tbSuffix = 'user_relation';

    /**
     * 主键
     * @var string
     */
    protected $primary = 'idx0';

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
     * 初始化
     * @param $DI
     */
    public function __construct($DI)
    {
        $this->DI = $DI;
        $this->redis = \Util\RedisClient::getInstance($DI);
        $this->cache_big_v_set =
            \Util\ReadConfig::get('redis_cache_keys.big_v_set', $DI);
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
        $model = $this->getPartitionModel($uid);
        $result = $model->setField('status')->setFilter(array(
            array('friend_uid', '=', $friend_uid)
        ))->find($uid);
        return isset($result[0]) ? intval($result[0]['status']) : -99;
    }

    /**
     * 创建好友关系
     *
     *       0  : 表示 A关注了B，但B没有关注A, 此时A是B的粉丝
     *       1  : 表示 A关注了B，B也关注了A，此时A和B互粉，自动升级为好友关系
     *
     * @param $uid
     * @param $friend_uid
     * @return int
     */
    public function createRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($uid, $friend_uid);

        $tempStatus = $status;

        $uidModel = $this->getPartitionModel($uid);
        $friendUidModel = $this->getPartitionModel($friend_uid);

        switch ($status) {
            case -99:
                $status = 0;
                $time = time();
                $uidModel->insert(array(
                    'uid' => $uid,
                    'friend_uid' => $friend_uid,
                    'status' => 0,
                    'create_at' => $time,
                ));
                $friendUidModel->insert(array(
                    'uid' => $friend_uid,
                    'friend_uid' => $uid,
                    'status' => 2,
                    'create_at' => $time,
                ));
                break;

            case -1:
                $status = 0;
                $uidModel->setFilter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 0
                    ));
                $friendUidModel->setFilter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 2
                    ));
                break;

            case 2:
                $status = 1;
                $uidModel->setFilter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 1
                    ));
                $friendUidModel->setFilter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 1
                    ));
                break;
        }

        if (in_array($tempStatus, array(-99, -1, 2))) {
            $countModel = new UserCountModel($this->DI);
            $countModel->updateCount($uid, 'follow_count', 1, true);
            $countModel->updateCount($friend_uid, 'fans_count', 1, true);

            $countModel->setBigv($friend_uid);
        }

        return $status;
    }

    /**
     * 解除好友关系
     *
     *    -1  : 表示 A和B不是好友关系，但数据库存在记录，但A和B已经同时取消了关注
     *    2  : 表示 B关注了A，但A没有关注B，此时B是A的粉丝
     *
     * @param $uid
     * @param $friend_uid
     * @return int
     */
    public function removeRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($uid, $friend_uid);

        $tempStatus = $status;

        $uidModel = $this->getPartitionModel($uid);
        $friendUidModel = $this->getPartitionModel($friend_uid);

        switch ($status) {
            case 0:
                $status = -1;
                $uidModel->setFilter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => -1
                    ));
                $friendUidModel->setFilter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => -1
                    ));
                break;

            case 1:
                $status = 2;
                $uidModel->setFilter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status' => 2
                    ));
                $friendUidModel->setFilter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status' => 0
                    ));
                break;
        }

        if (in_array($tempStatus, array(0, 1))) {
            $countModel = new UserCountModel($this->DI);
            $countModel->updateCount($uid, 'follow_count', 1, false);
            $countModel->updateCount($friend_uid, 'fans_count', 1, false);

            $countModel->setBigv($friend_uid);
        }

        return $status;
    }

    /**
     * 关注列表
     * @param $uid
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getFollowList($uid, $offset = 0, $limit = 15)
    {
        $model = $this->getPartitionModel($uid);
        $results = $model
            ->setField('friend_uid')
            ->setLimit($offset, $limit)
            ->setFilter(array(
                array('status', '>=', 0),
                array('status', '<=', 1),
            ))->find($uid);

        $rets = array();
        if($results) {
            foreach($results as $result) {
                $rets[] = $result['friend_uid'];
            }
        }

        unset($results);

        return $rets;
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
        $model = $this->getPartitionModel($uid);
        $results = $model
            ->setField('friend_uid')
            ->setLimit($offset, $limit)
            ->setFilter(array(
                array('status', '>', 0),
            ))->find($uid);
        return $results;
    }

    /**
     * 好友关系
     *      1:N关系
     * @param $uid
     * @param $friend_uids
     * @return array
     */
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
        $model = $this->getPartitionModel($uid);

        $rets = array();
        foreach($friend_uids as $_fuid) {
            $result = $model
                ->setField('friend_uid,status')
                ->setFilter(array(
                    array('friend_uid', '=', $_fuid)
                ))->find($uid);
            if($result) {
                $rets[] = $result[0];
            }
        }

        return $rets;
    }

    /**
     * 数据转换, 变成键值关联数据
     * @param $data
     * @return array
     */
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