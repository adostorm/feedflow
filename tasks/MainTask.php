<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task {

    public function test0Action() {
        $u = new UserRelationModel($this->getDI());
//        $result = $u->field('uid,friend_uid')->find(3);
//        var_dump($result);

        $u->insert(array(
            'uid'=>1,
            'friend_uid'=>3,
        ));
    }

    public function test1Action() {
        $this->redis = \Util\RedisClient::getInstance($this->getDI());
        $cache_key_big_v_set = \Util\ReadConfig::get('redis_cache_keys.big_v_set', $this->getDI());


        $this->redis->pipeline();
        for($i=200; $i<2000; $i++) {
            $this->redis->hset($cache_key_big_v_set, $i, $i);
        }
        $this->redis->exec();


        $uid = 0;

        $user = new UserRelation($this->getDI());
        $ids = $user->getFollowList($uid);

        $ids = range(1, 10000);

        $result = $this->redis->hmGet($cache_key_big_v_set, $ids);

        $tmp = array();
        foreach($result as $k=>$v) {
            if($v) {
                $tmp[] = $k;
            }
        }


        var_dump($tmp);
    }

    public function test2Action() {
        FeedRelation::find(array(
            "uid=:uid:",
            'order'=>'create_at desc',
            'limit'=>array(
                'number'=>200,
                'offset'=>0,
            ),
            'bind'=>array(
                'uid'=>1,
            ),
        ));
    }

}