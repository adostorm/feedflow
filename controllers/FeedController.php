<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:46
 */

use Redisc\Client as Redis;

class FeedController extends ControllerBase {

    public function getFeedByAppId() {

        echo 1;exit;

        $app_id = $this->request->get('app_id');

        $config = $this->getDI()->get('config');

        $redisConfig = $config->{'redis'}->{'link_master0'};

        $cache_key_appfeed = $config->{'appfeed'};

        $redis = new Redis($redisConfig->{'host'}, $redisConfig->{'port'});

        $results = $redis->zrange($cache_key_appfeed, 0, -1);

        var_dump($results);
    }

}