<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午10:38
 */

class UserController extends CController
{

    public function isFriend()
    {

        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(400, 2001, '用户ID不能为空');
        } else if (empty($friend_uid)) {
            throw new \Util\APIException(400, 2002, '好友ID不能为空');
        }

        $user = new UserRelationModel($this->getDI());
        $status = $user->checkRelation($friend_uid, $uid);

        if ($status == -98) {
            throw new \Util\APIException(403, 2003, '不能关注自己');
        } else if ($status == -99 || $status == -1) {
            throw new \Util\APIException(400, 2004, '不是好友');
        }

        switch ($status) {
            case -99:
            case -1:
                $msg = '未关注';
                break;
            case 2:
                $msg = '粉丝';
                break;
            case 1:
                $msg = '互粉';
                break;
            default:
                $msg = '未知异常';
                break;
        }

        $this->render(array(
            'status' => $status
        ), $msg);
    }

    public function getFansList()
    {
        $uid = $this->request->get('uid', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        } else if ($uid < 0) {
            throw new \Util\APIException(200, 2002, '用户ID不正确');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $user = new UserRelation();
        $result = $user->getFansList($uid, $offset, $count);

        $this->render($result);
    }

    public function getFollowList()
    {
        $uid = $this->request->get('uid', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $user = new UserRelation();
        $result = $user->getFollowList($uid, $offset, $count);

        $this->render($result);
    }

    public function addFollow()
    {
        $app_id = $this->request->getPost('app_id', 'int');
        $repush = $this->request->getPost('repush', 'int');
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        } else if (empty($friend_uid)) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空');
        }

        $user = new UserRelationModel($this->getDI());
        $result = $user->createRelation($uid, $friend_uid);

        if ($result == -98) {
            throw new \Util\APIException(200, 2003, '不能关注自己');
        }

        if (in_array($result, array(1, 2))) {
            $msg = '关注成功';
        } else {
            $msg = '关注失败';
        }

        $this->render(array(
            'status' => $result,
        ), $msg);
    }

    public function unFollow()
    {

        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        } else if (empty($friend_uid)) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空');
        }

        $user = new UserRelationModel($this->getDI());
        $result = $user->removeRelation($uid, $friend_uid);

        if ($result == -98) {
            throw new \Util\APIException(200, 2003, '不能关注自己');
        } else if ($result == -99) {
            throw new \Util\APIException(200, 2004, '不是好友');
        }

        if (in_array($result, array(-1, 0))) {
            $msg = '取消成功';
        } else {
            $msg = '取消失败';
        }

        $this->render(array(
            'status' => $result,
        ), $msg);
    }

    public function getRelations() {
        $uid = $this->request->getQuery('uid', 'int');
        $friend_uids = $this->request->getQuery('friend_uids');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        } else if (empty($friend_uids)) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空');
        }

        if(is_string($friend_uids)) {
            $tmp = array();
            $friend_uids = str_replace('，',',', $friend_uids);
            foreach(explode(',', $friend_uids) as $_fuid) {
                $tmp[] = $_fuid;
            }
            $friend_uids = $tmp;
            unset($tmp);
        }

        $userRelationModel = new UserRelationModel($this->getDI());
        $results = $userRelationModel->getInRelationList($uid, $friend_uids);

        $rets = array();
        if ($results) {
            $results = $userRelationModel->transfer($results);
            foreach ($friend_uids as $_uid) {
                $rets[] = array(
                    'uid' => (int) $_uid,
                    'status' => isset($results[$_uid]) ? (int) $results[$_uid] : -1,
                );
            }
        } else {
            foreach ($friend_uids as $fid) {
                $rets[] = array(
                    'uid' => (int)$fid,
                    'status' => -1
                );
            }
        }

        $this->render(array(
            'status' => $rets,
        ));
    }

}