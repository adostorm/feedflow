<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午11:22
 */

class UserRelationModel extends \HsMysql\Model
{

    public $dbname = 'userstate';

    public $tbname = 'user_relation';

    public $index = 'idx0';

    public function __construct($di) {
        parent::__construct($di, '');
    }

    public function checkRelation($uid, $friend_uid)
    {
        $this->setIsAssociate(false);
        $result = $this->field('status')->filter(array(
            array('friend_uid', '=', $friend_uid)
        ))->find($uid);
        return isset($result[0]) ? intval($result[0]) : -99;
    }

    public function createRelation($uid, $friend_uid, $status)
    {
        $status = intval($status);
        switch($status) {
            case -99:
                $status = 2;
                $time = time();
                $this->insert(array(
                    'uid'=>$uid,
                    'friend_uid'=>$friend_uid,
                    'status'=>0,
                    'create_at'=>$time,
                ));
                $this->insert(array(
                    'uid'=>$friend_uid,
                    'friend_uid'=>$uid,
                    'status'=>$status,
                    'create_at'=>$time,
                ));
                break;

            case -1:
                $status = 2;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status'=>0
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status'=>$status
                    ));
                break;

            case 0:
                $status = 1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status'=>1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status'=>$status
                    ));
                break;
        }

        return $status;
    }

    public function removeRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($uid, $friend_uid);

        switch($status) {
            case 2:
                $status = -1;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status'=>-1
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status'=>-$status
                    ));
                break;

            case 1:
                $status = 0;
                $this->filter(array(
                    array('friend_uid', '=', $friend_uid),
                ))->update($uid, array(
                        'status'=>2
                    ));
                $this->filter(array(
                    array('friend_uid', '=', $uid),
                ))->update($friend_uid, array(
                        'status'=>$status
                    ));
                break;
        }

        return $status;
    }

    public function getRelationList()
    {

    }

    public function getFollowList($uid, $limit, $offset)
    {
        $result = $this->filter(array(
            array('status', '=', 1)
        ))->limit($offset, $limit)->find($uid);
        return $result;
    }

    public function getFansList($uid, $limit, $offset)
    {
        $result = $this->filter(array(
            array('status', '>', 0)
        ))->limit($offset, $limit)->find($uid);
        return $result;
    }

}