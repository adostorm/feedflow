<?php

$config = new \Phalcon\Config(array(
    'database' => array(
        'adapter' => 'Mysql',
        'host' => 'localhost',
        'username' => 'root',
        'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'dbname' => 'test',
        'hs_write_port' => 9998,
        'hs_read_port' => 9999,
    ),
    'application' => array(
        'libraryDir' => __DIR__ . '/../library/',
        'controllersDir' => __DIR__ . '/../controllers/',
        'vendorDir' => __DIR__ . '/../vendor/',
        'modelsDir' => __DIR__ . '/../models/',
        'viewsDir' => __DIR__ . '/../views/',
        'baseUri' => '/pj_feed/',
        'path' => __DIR__ . '/../',
        'log' => __DIR__ . '/../log/',
    ),
    'beanstalk' => array(
        'link_queue0' => array(
            'host' => '127.0.0.1',
            'port' => 11307,
        ),
    ),
    'setting' => array(
        'cache_timeout_alg1'=>2592000, // a month
        'big_v_level'=>300,
    ),
    'link_userstate' => array(
        'host' => '127.0.0.1',
        'username'=>'root',
        'password' => '123456',
        'dbname' => 'userstate',

        'hs_passwd'=>'5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_feed' => array(
        'host' => '127.0.0.1',
        'username'=>'root',
        'password' => '123456',
        'dbname' => 'feed',

        'hs_passwd'=>'5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
    ),
    'link_feedstate'=>array(
        'host' => '127.0.0.1',
        'username'=>'root',
        'password' => '123456',
        'dbname' => 'feedstate',

        'hs_passwd'=>'5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
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
        'user_id_feeds' => 'cache:user:%d:feeds',
        'user_id_counts' => 'cache:user:%d:counts',
        'feed_id_content'=>'cache:feed:%d:content',
        'follow_uid_list'=>'cache:follow:%d:list',
        'fans_uid_list'=>'cache:fans:%d:list',
        'feed_uid_push'=>'cache:feed:%d:push',
        'big_v_set'=> 'cache:big_v_set',
    ),

    'queue_connect' => array(
        'host' => '127.0.0.1',
        'port' => '11980',
    ),

    'queue_keys' => array(
        'allfeeds' => 'queue:allfeeds',
        'pushfeeds' => 'queue:%id:feeds',
    ),

));

return $config;
