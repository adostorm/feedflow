<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-29
 * Time: 上午10:38
 */

class UserController extends CController
{

    /**
     * 判断是否好友关系
     *      -1 不是好友
     *      2 粉丝
     *      1 互粉
     *      0 关注
     *      default 其它异常
     *
     * @throws Util\APIException
     */
    public function isFriend()
    {
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid) || $uid < 0) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        } else if (empty($friend_uid) || $friend_uid < 0) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空或不能为负值');
        }

        $user = new UserRelationModel($this->getDI());
        $status = $user->checkRelation($uid, $friend_uid);

        if ($status == -98) {
            throw new \Util\APIException(403, 2003, '自己');
        }

        switch ($status) {
            case -99:
            case -1:
                $status = -1;
                $msg = '不是好友';
                break;

            case 2:
                $msg = '粉丝';
                break;
            case 1:
                $msg = '互粉';
                break;
            case 0:
                $msg = '关注';
                break;
            default:
                $msg = '未知异常';
                break;
        }

        $this->render(array(
            'relation' => $status
        ), $msg);
    }


    /**
     * 粉丝列表
     * @throws Util\APIException
     */
    public function getFansList()
    {
        $uid = $this->request->getQuery('uid', 'int');
        $page = $this->request->getQuery('page', 'int');
        $count = $this->request->getQuery('count', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        } else if ($uid < 0) {
            throw new \Util\APIException(200, 2002, '用户ID不正确');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $userCountModel = new UserCountModel($this->getDI());
        $total = $userCountModel->getCountByField($uid, 'fans_count');

        $result = array();
        if ($total > 0) {
            $user = new UserRelation();
            $result = $user->getFansList($uid, $offset, $count);
        }

        $this->render(array(
            'list' => $result,
            'total' => $total
        ));
    }

    /**
     * 关注列表
     * @throws Util\APIException
     */
    public function getFollowList()
    {
        $uid = $this->request->getQuery('uid', 'int');
        $page = $this->request->getQuery('page', 'int');
        $count = $this->request->getQuery('count', 'int');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;

        $userCountModel = new UserCountModel($this->getDI());
        $total = $userCountModel->getCountByField($uid, 'follow_count');

        $result = array();
        if ($total > 0) {
            $user = new UserRelation();
            $result = $user->getFollowList($uid, $offset, $count);
        }

        $this->render(array(
            'list' => $result,
            'total' => $total
        ));
    }

    /**
     * 添加关注
     * @throws Util\APIException
     */
    public function addFollow()
    {
        $app_id = $this->request->getPost('app_id', 'int');
        $repush = $this->request->getPost('repush', 'int');
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid) || $uid < 0) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        } else if (empty($friend_uid) || $friend_uid < 0) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空或不能为负值');
        }

        $user = new UserRelationModel($this->getDI());
        $result = $user->createRelation($uid, $friend_uid);

        if ($result == -98) {
            throw new \Util\APIException(200, 2003, '用户不能自己关注自己');
        }

        if (in_array($result, array(0, 1))) {
            $_status = 1;
            $msg = '关注成功';
        } else {
            $result = -1;
            $_status = -1;
            $msg = '关注失败';
        }

        $this->render(array(
            'status' => (int)$_status,
            'relation' => (int)$result,
        ), $msg);
    }

    /**
     * 取消关注
     * @throws Util\APIException
     */
    public function unFollow()
    {
        $uid = $this->request->getPost('uid', 'int');
        $friend_uid = $this->request->getPost('friend_uid', 'int');

        if (empty($uid) || $uid < 0) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        } else if (empty($friend_uid) || $friend_uid < 0) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空或不能为负值');
        }

        $user = new UserRelationModel($this->getDI());
        $result = $user->removeRelation($uid, $friend_uid);

        if ($result == -98) {
            throw new \Util\APIException(200, 2003, '用户不能自己取消关注自己');
        } else if ($result == -99) {
            throw new \Util\APIException(200, 2004, '不是好友,取消关注失败');
        }

        if (in_array($result, array(-1, 2))) {
            $_status = 1;
            $msg = '取消关注成功';
        } else {
            $_status = -1;
            $msg = '取消关注失败';
        }

        $this->render(array(
            'status' => $_status,
            'relation' => $result,
        ), $msg);
    }

    /**
     * 取得用户关系
     * @throws Util\APIException
     */
    public function getRelations()
    {
        $uid = $this->request->getQuery('uid', 'int');
        $friend_uids = trim($this->request->getQuery('friend_uids'));

        if (empty($uid) || $uid < 0) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        } else if (empty($friend_uids)) {
            throw new \Util\APIException(200, 2002, '好友ID不能为空或不能为负值');
        }

        if (is_string($friend_uids)) {
            $tmp = array();
            $friend_uids = str_replace('，', ',', $friend_uids);
            foreach (explode(',', $friend_uids) as $_fuid) {
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
            foreach ($friend_uids as $_uid) {
                $rets[] = array(
                    'uid' => (int) $_uid,
                    'status' => -1
                );
            }
        }

        $this->render($rets);
    }

    /**
     * 获取 关注数、粉丝数
     * @throws Util\APIException
     */
    public function getCounts()
    {
        $uids = $this->request->getQuery('uids');
        if (empty($uids)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        }

        if (is_string($uids)) {
            $tmp = array();
            $uids = str_replace('，', ',', $uids);
            $uids = explode(',', $uids);
            if(is_array($uids) && $uids) {
                $uids = array_unique($uids);
                foreach ($uids as $_uid) {
                    if($_uid > 0) {
                        $tmp[] = (int) $_uid;
                    }
                }
            }
            $uids = $tmp;
            unset($tmp);
        }
        $rets = array();
        if($uids) {
            $userCountModel = new UserCountModel($this->getDI());
            foreach ($uids as $uid) {
                $result = $userCountModel->getCountByUid(intval($uid));
                if ($result) {
                    $rets[] = array(
                        'uid' => (int) $uid,
                        'follow_count' => (int) $result['follow_count'],
                        'fans_count' => (int) $result['follow_count'],
                    );
                    unset($result);
                } else {
                    $rets[] = array(
                        'uid' => (int) $uid,
                        'follow_count' => 0,
                        'fans_count' => 0,
                    );
                }
            }
        }

        $this->render($rets);
    }

    /**
     * 获取 动态数 未读动态数
     * @throws Util\APIException
     */
    public function getFeedCount() {
        $app_id = (int) $this->request->getQuery('app_id');
        $uid = (int) $this->request->getQuery('uid');

        if (empty($uid)) {
            throw new \Util\APIException(200, 2001, '用户ID不能为空或不能为负值');
        } else if (empty($app_id)) {
            throw new \Util\APIException(200, 2002, '应用ID不能为空或不能为负值');
        }

        $userFeedCountModel = new UserFeedCountModel($this->getDI());
        $result = $userFeedCountModel->getCountByUid($app_id, $uid);

        if ($result) {
            $rets = array(
                'uid' => (int) $uid,
                'app_id' => (int) $result['app_id'],
                'feed_count' => (int) $result['feed_count'],
                'unread_count' => (int) $result['unread_count'],
            );
            unset($result);
        } else {
            $rets = array(
                'uid' => (int) $uid,
                'app_id' => $app_id,
                'feed_count' => 0,
                'unread_count' => 0,
            );
        }

        $this->render($rets);
    }

}