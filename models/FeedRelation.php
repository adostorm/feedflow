<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午6:03
 */

class FeedRelation extends AdvModel
{

    /**
     * 用户ID
     * @var int
     */
    public $uid = 0;

    /**
     * 用户ID
     * @var int
     */
    public $friend_uid = 0;

    /**
     * Feed系统feed的ID
     * @var int
     */
    public $feed_id = 0;

    /**
     * 权重
     * @var int
     */
    public $weight = 0;

    /**
     * 好友缓存的Key
     * @var string
     */
    public $cache_friend = '';

    /**
     * 用户动态缓存key
     * @var string
     */
    public $cache_timeline = '';

    /**
     * 用户动态缓存过期时间
     * @var string
     */
    public $cache_timeline_ttl = '';

    /**
     * 缓存Client
     * @var null
     */
    public $redis = null;

    /**
     * 数据库名称
     * @var string
     */
    public $dbLink = 'link_db_feedstate';

    /**
     * 表名称
     * @var string
     */
    public $tbSuffix  = 'feed_relation';


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


    /**
     * 获得Redis用户动态的总数, 最大值为200
     * @param $app_id
     * @param $uid
     * @return mixed
     */
    public function getFollowFeedsCount($app_id, $uid)
    {
        $key = sprintf($this->cache_friend, $app_id, $uid);
        return $this->redis->zcard($key);
    }


    /**
     * 根据app的Id，用户ID获取用户的动态数据
     *
     * 推拉结合:
     *  读取用户接收到的数据+大V的数据
     *
     * 建立缓存，按时间自动排序
     *
     * @param $app_id
     * @param $uid
     * @param int $offset
     * @param int $limit
     * @return array
     */
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
        }
        unset($results);

        return $rets;
    }

    /**
     * 好友的动态
     * @param $app_id
     * @param $uid
     * @param int $offset
     * @param int $limit
     * @param array $extras
     * @return array
     */
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