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

    public $cache_friend = '';

    public $cache_timeline = '';

    public $cache_timeline_ttl = '';

    public $redis = null;

    public $dbname = 'db_feedstate';

    public $tbname  = 'feed_relation';

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
        $this->cache_friend =
            \Util\ReadConfig::get('redis_cache_keys.friend_appid_id_feeds', $this->getDI());
        $this->cache_timeline =
            \Util\ReadConfig::get('redis_cache_keys.friend_appid_id_feeds_timeline', $this->getDI());
        $this->cache_timeline_ttl =
            \Util\ReadConfig::get('redis_cache_keys.friend_appid_id_feeds_timeline_ttl', $this->getDI());
    }


    public function getFollowFeedsCount($app_id, $uid)
    {
        $key = sprintf($this->cache_friend, $app_id, $uid);
        return $this->redis->zcard($key);
    }


    public function getFollowFeedsByUid($app_id, $uid, $offset = 0, $limit = 15)
    {
        $key = sprintf($this->cache_friend, $app_id, $uid);

        $timeline_key = sprintf($this->cache_timeline, $app_id, $uid);
        $timeline = $this->redis->get($timeline_key);
        if (!$timeline) {
            $timeline = 0;
        }

        $timeline_ttl_key = sprintf($this->cache_timeline_ttl, $app_id, $uid);
        $timeline_ttl = (int) $this->redis->ttl($timeline_ttl_key);

        if ($offset == 0 && $timeline_ttl <= 0) {
            $models = $this->getRelationFeedList($app_id, $uid, 0, 200, array(
                'create_at'=>$timeline,
            ));

            if ($model_length = count($models)) {
                $expire = \Util\ReadConfig::get('setting.cache_timeout_t2', $this->getDI());
                $this->redis->set($timeline_ttl_key, 1, $expire);
                $this->redis->set($timeline_key
                    , $models[$model_length - 1]['create_at']);
                $this->redis->pipeline();
                foreach ($models as $model) {
                    $this->redis->zadd($key, -$model['create_at']
                        , msgpack_pack($model));
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
                $offsetF = ($pageF - 1) * ($countF - 1);

                $bigvs = array_merge($bigvs, $userCount->diffBigv($follow_ids));

                if (count($follow_ids) <= $countF) {
                    break;
                }
            }

            if (is_array($bigvs) && $bigvs) {
                $bigvs = array_unique($bigvs);
                $userFeed = new UserFeed();
                foreach ($bigvs as $_uid) {
                    $bigFriendFeeds = $userFeed->getFeedListByAppIdAndUid($app_id, $_uid, array(
                        'offset' => 0,
                        'limit' => 20,
                        'timeline' => $timeline,
                        'fields' => 'app_id,uid,feed_id,creat_at',
                        'order' => 'create_at desc'
                    ));

                    if ($model_length = count($bigFriendFeeds)) {
                        $this->redis->pipeline();
                        foreach ($bigFriendFeeds as $_feed) {
                            $this->redis->zadd($key, -$_feed['creat_at']
                                , msgpack_pack($_feed));
                        }
                        $this->redis->exec();
                    }
                }

                if ($this->redis->zcard($key) > 200) {
                    $this->redis->zremrangebyrank($key, 201, -1);
                }
            }

        }

        $results = $this->redis->zrange($key, $offset, $limit);

        $rets = array();
        if ($results) {
            foreach ($results as $result) {
                $rets[] = msgpack_unpack($result);
            }

            $userFeedCountModel = new UserFeedCountModel($this->getDI());
            $userFeedCountModel->update($uid, array(
                'unread_count' => 0
            ));
        }
        unset($results);

        return $rets;
    }

    public function getRelationFeedList($app_id, $uid, $offset=0, $limit=10, $extras=array()) {
        $this->init($uid);
        $results = $this->find(array(
            'app_id=:app_id: and uid=:uid: and create_at>=:create_at:',
            'columns' => 'app_id,uid,feed_id,create_at',
            'order' => 'create_at desc',
            'limit' => array(
                'offset' => $offset,
                'number' => $limit,
            ),
            'bind' => array(
                'uid' => $uid,
                'app_id' => $app_id,
                'create_at' => isset($extras['create_at']) ? (int) $extras['create_at'] : 0,
            ),
        ));

        $rets = array();

        if ($results->getFirst()) {
            $feedHsModel = new FeedModel($this->getDI());
            foreach ($results as $result) {
                $rets[] = $feedHsModel->getById($result->uid, $result->feed_id);
            }
        }

        return $rets;
    }

}