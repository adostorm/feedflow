<?php

$config = new \Phalcon\Config(array(
    'application' => array(
        'libraryDir' => __DIR__ . '/../library/',
        'controllersDir' => __DIR__ . '/../controllers/',
        'vendorDir' => __DIR__ . '/../vendor/',
        'modelsDir' => __DIR__ . '/../models/',
        'models_hsDir' => __DIR__ . '/../models_hs/',
        'viewsDir' => __DIR__ . '/../views/',
        'baseUri' => '/pj_feed/',
        'path' => __DIR__ . '/../',
        'log' => __DIR__ . '/../log/',
    ),
    'beanstalk' => array(
        'link_queue0' => array(
            'host' => '127.0.0.1',
            'port' => 11980,
        ),
        'link_queue1' => array(
            'host' => '127.0.0.1',
            'port' => 11981,
        ),
    ),
    'setting' => array(
        'cache_timeout_t1' => 2592000, // a month
        'cache_timeout_t2' => 60, //friend feeds expire time and offset is zero
        'big_v_level' => 300,
    ),
    'link_db_countstate' => array(
        'host' => '127.0.0.1',
        'slave' => array(),
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'db_countstate',

        'hs_read_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_write_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_db_feedcontent' => array(
        'host' => '127.0.0.1',
        'slave' => array(),
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'db_feedcontent',

        'hs_read_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_write_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_db_feedstate' => array(
        'host' => '127.0.0.1',
        'slave' => array(),
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'db_feedstate',

        'hs_read_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_write_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_db_userfeed' => array(
        'host' => '127.0.0.1',
        'slave' => array(),
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'db_userfeed',

        'hs_read_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_write_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_db_userstate' => array(
        'host' => '127.0.0.1',
        'slave' => array(),
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'db_userstate',

        'hs_read_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_write_passwd' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'redis_connect' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
    ),
    'redis_cache_keys' => array(
        'app_id_feeds' => 'cache:app:%d:feeds',
        'friend_appid_id_feeds' => 'cache:app:%d:friend:%d:feeds',
        'friend_appid_id_feeds_timeline' => 'cache:app:%d:friend:%d:timeline',
        'me_appid_id_feeds' => 'cache:app:%d:me:%d:feeds',
        'user_id_counts' => 'cache:user:%d:counts',
        'feed_id_content' => 'cache:feed:%d:content',
        'follow_uid_list' => 'cache:follow:%d:list',
        'fans_uid_list' => 'cache:fans:%d:list',
        'feed_uid_push' => 'cache:feed:%d:push',
        'big_v_set' => 'cache:big_v_set',
    ),
    'queue_connect' => array(
        'host' => '127.0.0.1',
        'port' => '11980',
    ),
    'queue_keys' => array(
        'allfeeds' => 'queue_allfeeds',
        'pushfeeds' => 'queue_%d_feeds',
    ),

));

return $config;
