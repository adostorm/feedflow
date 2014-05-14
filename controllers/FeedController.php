<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:46
 */

class FeedController extends CController
{

    public function getFeedListByAppId()
    {
        $app_id = $this->request->get('app_id', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        if (!$app_id || $app_id < 0) {
            throw new \Util\APIException(200, 2101, 'app_id 不正确');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;
        $limit = $count * $page - 1;

        $di = $this->getDI();
        $redis = \Util\RedisClient::getInstance($di);
        $key = sprintf(\Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $di), $app_id);

        $max = 200;

        if ($limit > $max) {
            $total = $redis->zcard($key);
            if ($total > $max) {
                $total = $max;
            }
            $modValue = $total % $count;
            if ($modValue == 0) {
                $offset = $total - $count;
            } else {
                $offset = $total - $modValue;
            }
            $limit = $total - 1;
        }

        $results = $redis->zrange($key, $offset, $limit);

        $rets = array();
        if ($results) {
            foreach ($results as $result) {
                $rets[] = msgpack_unpack($result);
            }
            unset($results);
        }

        $this->render($rets);
    }

    public function getFeedListByUid()
    {
        $app_id = $this->request->get('app_id', 'int');
        $uid = $this->request->get('uid', 'int');
        $page = $this->request->get('page', 'int');
        $count = $this->request->get('count', 'int');

        if (!$app_id || $app_id < 0) {
            throw new \Util\APIException(200, 2101, 'app_id 不正确');
        } else if (!$uid || $uid < 0) {
            throw new \Util\APIException(200, 2102, 'uid 不正确');
        }

        $page = $page > 0 ? $page : 1;
        $count = $count > 0 && $count <= 50 ? $count : 15;

        $offset = ($page - 1) * $count;
        $limit = $count * $page - 1;

        $feedRelation = new FeedRelation();
        $result = $feedRelation->getFollowFeedsByUid($app_id, $uid, $offset, $limit);

        $this->render($result);
    }

    public function create()
    {
        $app_id = (int)$this->request->getPost('app_id');
        $source_id = (int)$this->request->getPost('source_id');
        $object_type = (int)$this->request->getPost('type');
        $object_id = (int)$this->request->getPost('type_id');
        $author_id = (int)$this->request->getPost('author_id');
        $author = $this->request->getPost('author');
        $content = $this->request->getPost('content');
        $create_at = $this->request->getPost('create_time');
        $attachment = $this->request->getPost('attachment');
        $extends = $this->request->getPost('extends');

        if (!$app_id || $app_id < 0) {
            throw new \Util\APIException(200, 2101, 'app_id 不正确');
        } else if (!$source_id || $source_id < 0) {
            throw new \Util\APIException(200, 2102, 'source_id 不正确');
        } else if (!$object_type || $object_type < 0) {
            throw new \Util\APIException(200, 2103, 'type 不正确');
        } else if (!$object_id || $object_id < 0) {
            throw new \Util\APIException(200, 2104, 'type_id 不正确');
        } else if (!$author_id || $author_id < 0) {
            throw new \Util\APIException(200, 2105, 'author_id 不正确');
        } else if (!$author || $author < 0) {
            throw new \Util\APIException(200, 2106, 'author 不能为空');
        } else if (!$content || $content < 0) {
            throw new \Util\APIException(200, 2107, 'content 不能为空');
        } else if (!$create_at || $create_at < 0) {
            throw new \Util\APIException(200, 2108, 'create_time 不正确');
        }

        $queue = \Util\BStalkClient::getInstance($this->getDI());
        $queue->choose(\Util\ReadConfig::get('queue_keys.allfeeds', $this->getDI()));

        if (!is_array($extends)) {
            $extends = strval($extends);
        }

        $data = array(
            'app_id' => $app_id,
            'source_id' => $source_id,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'author_id' => $author_id,
            'author' => $author,
            'content' => $content,
            'create_at' => $create_at,
            'attachment' => strval($attachment),
            'extends' => $extends,
        );

        $queue->put(msgpack_pack($data));

        $queue->disconnect();

        $this->render(array(
            'status' => 1
        ));
    }

}