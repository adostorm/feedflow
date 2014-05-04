<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午6:03
 */

class FeedRelation extends \Phalcon\Mvc\Model {

    public $uid = 0;

    public $friend_uid = 0;

    public $feed_id = 0;

    public $weight = 0;

    public $cache_app_id_feeds = '';

    public $cache_user_id_feeds = '';

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
        $this->setConnectionService('link_feedstate');
        $this->redis = \Util\RedisClient::getInstance($this->getDI());
        $this->cache_app_id_feeds = \Util\ReadConfig::get('redis_cache_keys.app_id_feeds', $this->getDI());
        $this->cache_user_id_feeds = \Util\ReadConfig::get('redis_cache_keys.user_id_feeds', $this->getDI());
    }

    public function getListByUid($uid) {

    }

    public function getListByAppId($app_id) {

    }

}