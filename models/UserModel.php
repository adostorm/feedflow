<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午11:22
 */

use HSocket\Model;
use HSocket\ModelProxy;
use HSocket\Config\IConfig;

class UserModel extends \Phalcon\Mvc\Model
{

    public function checkRelation($uid, $friend_uid)
    {
        try {
            $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
            $model = $proxy->getHandlerSocketModel(IConfig::READ_PORT);
            $index = $model->createIndex(Model::SELECT, 'user_relation', 'idx0', array('status'), 'friend_uid');

            $result = $index->find(array('=' => $friend_uid), 0, 0, array('filter' => array('=', 'friend_uid', $uid)));

            $status = -99;
            if ($result) {
                $status = $result[0][0];
            }

            return $status;
        } catch (\HandlerSocketException $e) {
            echo $e->getLine();
            echo "\n";
            echo $e->getMessage();
        } catch (\Phalcon\Exception $e) {
            echo $e->getLine();
            echo "\n";
            echo $e->getMessage();
        }
        return false;
    }

    public function createRelation($uid, $friend_uid, $status)
    {
        if (!in_array($status, array(-99, -1, 0))) {
            return false;
        }

        try {
            $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
            $model = $proxy->getHandlerSocketModel(IConfig::WRITE_PORT);

            $result = 0;
            if ($status == -99) {
                $handler = $model->openIndex(Model::INSERT, 'user_relation', 'idx0', array('uid', 'friend_uid', 'status', 'create_at'));
                $time = time();
                $handler->executeInsert(Model::INSERT, array(
                    'uid' => $uid,
                    'friend_uid' => $friend_uid,
                    'status' => 0,
                    'create_at' => $time,
                ));
                $handler->executeInsert(Model::INSERT, array(
                    'uid' => $friend_uid,
                    'friend_uid' => $uid,
                    'status' => 2,
                    'create_at' => $time,
                ));
            } else if ($status == -1) {
                $handler = $model->openIndex(Model::UPDATE, 'user_relation', 'idx0', array('status'), array('friend_uid'));
                $handler->executeUpdate(Model::UPDATE, '=', array($uid), array(0), 1, 0, array('F', '=', 0, $friend_uid));
                $handler->executeUpdate(Model::UPDATE, '=', array($friend_uid), array(2), 1, 0, array('F', '=', 0, $uid));
            } else if ($status == 0) {
                $handler = $model->openIndex(Model::UPDATE, 'user_relation', 'idx0', array('status'), array('friend_uid'));
                $handler->executeUpdate(Model::UPDATE, '=', array($uid), array(1), 1, 0, array('F', '=', 0, $friend_uid));
                $handler->executeUpdate(Model::UPDATE, '=', array($friend_uid), array(1), 1, 0, array('F', '=', 0, $uid));
            }

            return $result;
        } catch (\HandlerSocketException $e) {
            echo $e->getLine();
            echo "\n";
            echo $e->getMessage();
        } catch (\Phalcon\Exception $e) {
            echo $e->getLine();
            echo "\n";
            echo $e->getMessage();
        }
        return false;
    }

    public function removeRelation($uid, $friend_uid)
    {
        $status = $this->checkRelation($uid, $friend_uid);
        if($status == 0 || $status == -1 || $status == -99) { # 不是好友
            return false;
        }

        $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
        $model = $proxy->getHandlerSocketModel(IConfig::WRITE_PORT);
        $handler = $model->openIndex(Model::UPDATE, 'user_relation', 'idx0', array('status'), array('friend_uid'));
        if($status == 2) { # B已经是A的粉丝
            $status = -1;
            $handler->executeUpdate(Model::UPDATE, '=', array($uid), array($status), 1, 0, array('F', '=', 0, $friend_uid));
            $handler->executeUpdate(Model::UPDATE, '=', array($friend_uid), array(-1), 1, 0, array('F', '=', 0, $uid));
        } else if($status == 1) {
            $status = 2;
            $handler->executeUpdate(Model::UPDATE, '=', array($uid), array($status), 1, 0, array('F', '=', 0, $friend_uid));
            $handler->executeUpdate(Model::UPDATE, '=', array($friend_uid), array(0), 1, 0, array('F', '=', 0, $uid));
        }
        return $status;
    }

    public function getRelationList()
    {

    }

    public function getFollowList($uid, $limit, $offset)
    {
        $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
        $model = $proxy->getHandlerSocketModel(IConfig::READ_PORT);
        $handler = $model->openIndex(Model::SELECT, 'user_relation', 'idx0',
            array('uid','friend_uid','status','create_at'), array('status'));
        $result = $handler->executeSingle(\HSocket\Model::SELECT, '=', array($uid), $limit, $offset, null, null, array('F', '=', 0, 1));
        return $result;
    }

    public function getFansList($uid, $limit, $offset)
    {
        $proxy = new ModelProxy($this->getDI()->get('config'), 'link_user_relation');
        $model = $proxy->getHandlerSocketModel(IConfig::READ_PORT);
        $handler = $model->openIndex(Model::SELECT, 'user_relation', 'idx0',
            array('uid','friend_uid','status','create_at'), array('status'));
        $result = $handler->executeSingle(\HSocket\Model::SELECT, '=', array($uid), $limit, $offset, null, null, array('F', '>', 0, 0));
        return $result;
    }

}