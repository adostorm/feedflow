<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午6:03
 */

class FeedRelation extends CCommonModel {

    public $uid = 0;

    public $friend_uid = 0;

    public $feed_id = 0;

    public $weight = 0;

    public $cache_app_id_feeds = '';

    public $cache_friend_appid_id_feeds = '';

    public $cache_me_appid_id_feeds = '';

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
        $this->cache_app_id_feeds = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $this->getDI());
        $this->cache_friend_appid_id_feeds = \Util\ReadConfig::get('redis_cache_keys.friend_appid_id_feeds', $this->getDI());
        $this->cache_me_appid_id_feeds = \Util\ReadConfig::get('redis_cache_keys.me_appid_id_feeds', $this->getDI());
        $this->cache_timeline = \Util\ReadConfig::get('redis_cache_keys.friend_id_feeds_timeline', $this->getDI());
        parent::initialize();
    }



    public function getFollowFeedsByUid($app_id, $uid, $offset=0, $limit=15) {

        $timeline_key = sprintf($this->cache_timeline, $app_id, $uid);
        $redis = \Util\RedisClient::getInstance($this->getDi());

        $timeline = $this->redis->get($timeline_key);
        if(!$timeline) {
            $timeline = 0;
        }

        $key = sprintf($this->cache_friend_appid_id_feeds, $uid);

        $results = FeedRelation::find(array(
            "uid=:uid: and app_id=:app_id: and create_at>:timeline:",
            'order'=>'create_at desc',
            'limit'=>array(
                'number'=>200,
                'offset'=>0,
            ),
            'bind'=>array(
                'uid'=>$uid,
                'app_id'=>$app_id,
                'create_at'=>$timeline,
            ),
        ));

        if($results) {
            $element = end($results);
            $this->redis->set($this->cache_timeline, $element['create_at']);
            reset($results);
        }

        $userRelation = new UserRelationModel($this->getDI());
        $userCount = new UserCountModel($this->getDI());

        $bigvs = array();
        $pageF = 1;
        $offsetF = 0;
        $countF = 1001;
        while($follow_ids = $userRelation->getFollowList($uid, $offsetF, $countF)) {
            $offsetF = ($pageF - 1) * $countF - 1;

            $bigvs = array_merge($bigvs, $userCount->diffBigV($follow_ids));

            if(count($follow_ids) <= $countF) {
                break;
            }
        }

        if($bigvs) {
            foreach($bigvs as $k=>$v) {
                if($v) {
                    $bigFriendFeeds = $this->redis->zrange(sprintf($this->cache_me_appid_id_feeds, $k), 0, 20);
                    $this->redis->pipeline();
                    foreach($bigFriendFeeds as $feed) {
                        $this->redis->zadd($key, -$feed['create_at'], $feed);
                    }
                    $this->redis->exec();
                }
            }
        }

        //如果大于500条，裁剪掉

        $results = $redis->zrange($key, $offset, $limit);
        return $results;
    }

}