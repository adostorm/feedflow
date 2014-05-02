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
    'setting' => array(),
    'link_userstate' => array(
        'host' => '127.0.0.1',
        'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
        'dbname' => 'userstate',
    ),
    'link_feed' => array(
        'host' => '127.0.0.1',
        'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'hs_read_port' => 9998,
        'hs_write_port' => 9999,
        'dbname' => 'feed',
    ),

    'redis_connect' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
    ),

    'redis_cache_keys' => array(
        'app_id_feeds' => 'cache:app:%d:feeds',
        'user_id_feeds' => 'cache:user:%d:feeds',
    ),

    'queue_connect' => array(
        'host' => '127.0.0.1',
        'port' => '11980',
    ),

    'queue_keys' => array(
        'allfeeds' => 'queue:allfeeds',
    ),

));

return $config;
