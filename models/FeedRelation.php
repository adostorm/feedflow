<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午6:03
 */

class FeedRelation extends AdvModel
{

    public $uid = 0;

    public $friend_uid = 0;

    public $feed_id = 0;

    public $weight = 0;

    public $cache_friend_appid_id_feeds = '';

    public $cache_timeline = 0;

    public $redis = null;



    /**
     * @param int $feed_id
     */
    public function setFeedId($feed_id)
    {
        $this->feed_id = $feed_id;
    }

    /**
     * @return int
     */
    public function getFeedId()
    {
        return $this->feed_id;
    }

    /**
     * @param int $friend_uid
     */
    public function setFriendUid($friend_uid)
    {
        $this->friend_uid = $friend_uid;
    }

    /**
     * @return int
     */
    public function getFriendUid()
    {
        return $this->friend_uid;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }


    public function initialize()
    {

        $this->redis = \Util\RedisClient::getInstance($this->getDI());
        $this->cache_friend_appid_id_feeds =
            \Util\ReadConfig::get('redis_cache_keys.friend_appid_id_feeds', $this->getDI());
        $this->cache_timeline =
            \Util\ReadConfig::get('redis_cache_keys.friend_id_feeds_timeline', $this->getDI());
    }


    public function getFollowFeedsCount($app_id, $uid) {
        $key = sprintf($this->cache_friend_appid_id_feeds, $app_id, $uid);
        return $this->redis->zcard($key);
    }


    public function getFollowFeedsByUid($app_id, $uid, $offset = 0, $limit = 15)
    {
        $key = sprintf($this->cache_friend_appid_id_feeds, $app_id, $uid);

        $timeline_key = sprintf($this->cache_timeline, $app_id, $uid);
        $timeline = $this->redis->get($timeline_key);
        if (!$timeline) {
            $timeline = 0;
        }

        $expire = \Util\ReadConfig::get('setting.cache_timeout_t2', $this->getDI());
        if ($offset == 0 && ((time() - $timeline) > $expire)) {
            $this->init($uid);
            $this->redis->delete($key);
            $userFeed = new UserFeed();
            $models = $userFeed->find(array(
                "uid=:uid: and app_id=:app_id: and create_at>:timeline:",
                'order' => 'create_at desc',
                'limit' => array(
                    'number' => 200,
                    'offset' => 0,
                ),
                'bind' => array(
                    'uid' => $uid,
                    'app_id' => $app_id,
                    'create_at' => $timeline,
                ),
            ));

            if ($last = $models->getLast()) {
                $this->redis->set($this->cache_timeline, $last->create_at);
                $this->redis->pipeline();
                foreach ($models as $model) {
                    $this->redis->zadd($key, -$model->create_at
                        , msgpack_pack($model->toArray()));
                }
                $this->redis->exec();
            }

            $userRelation = new UserRelationModel($this->getDI());
            $userCount = new UserCountModel($this->getDI());
            $bigvs = array();
            $pageF = 1;
            $offsetF = 0;
            $countF = 1001;

            while ($follow_ids = $userRelation->getFollowList($uid, $offsetF, $countF)) {
                $offsetF = ($pageF - 1) * $countF - 1;

                $bigvs = array_merge($bigvs, $userCount->diffBigv($follow_ids));

                if (count($follow_ids) <= $countF) {
                    break;
                }
            }

            if (is_array($bigvs) && $bigvs) {
                $bigvs = array_unique($bigvs);

                foreach ($bigvs as $_uid) {
                    $bigFriendFeeds = $userFeed->getListByAppIdAndUid($app_id, $_uid, array(
                        'offset' => 0,
                        'limit' => 20,
                        'timeline' => $timeline,
                        'fields' => 'app_id,uid,feed_id,creat_at',
                        'order' => 'create_at desc'
                    ));

                    if ($bigFriendFeeds->getFirst()) {
                        $this->redis->pipeline();
                        foreach ($bigFriendFeeds as $_feed) {
                            $this->redis->zadd($key, -$_feed->creat_at
                                , msgpack_pack($_feed->toArray()));
                        }
                        $this->redis->exec();
                    }
                }

                if($this->redis->zcard($key) > 200) {
                    $this->redis->zremrangebyrank($key, 201, -1);
                }
            }

        }

        $results = $this->redis->zrange($key, $offset, $limit);

        return $results ? $results : array();
    }

}