<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午10:38
 */

class UserController extends CController {

    public function isFriend() {

        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        $user = new UserRelationModel($this->getDI());
        $status = $user->checkRelation($uid, $friend_uid);


    }

    public function getFansList() {
        $uid = $this->request->get('uid', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $user = new UserRelationModel($this->getDI());
        $result = $user->getFansList($uid, $count, $offset);

    }

    public function getFollowList() {
        $uid = $this->request->get('uid', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $user = new UserRelationModel($this->getDI());
        $result = $user->getFollowList($uid, $count, $offset);
    }

    public function addFollow() {
        $app_id = $this->request->getPost('app_id', 'int');
        $repush = $this->request->getPost('repush', 'int');
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        $user = new UserRelationModel($this->getDI());
        $status = $user->checkRelation($uid, $friend_uid);
        $result = $user->createRelation($uid, $friend_uid, $status);


    }

    public function unFollow() {
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        $user = new UserRelationModel($this->getDI());
        $result = $user->removeRelation($uid, $friend_uid);
    }

}