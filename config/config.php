<?php

$config = new \Phalcon\Config(array(
    'database' => array(
        'adapter'    => 'Mysql',
        'host'       => 'localhost',
        'username'   => 'root',
        'password'   => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
        'dbname'     => 'test',
        'hs_write_port' => 9998,
        'hs_read_port'=>9999,
    ),
    'application' => array(
        'libraryDir' => __DIR__ .'/../library/',
        'controllersDir' => __DIR__ .'/../controllers/',
        'vendorDir'     => __DIR__ . '/../vendor/',
        'modelsDir'      => __DIR__ . '/../models/',
        'viewsDir'      => __DIR__ . '/../views/',
        'baseUri'        => '/pj_feed/',
        'path' => __DIR__.'/../',
        'log' => __DIR__.'/../log/',
    ),
    'beanstalk' => array(
        'link_queue0' => array(
            'host'=>'127.0.0.1',
            'port'=>11307,
        ),
    ),

));

$db = include_once(__DIR__ . '/db.php');
$redis = include_once(__DIR__ . '/redis.php');
$cachekeys = include_once(__DIR__ . '/cachekeys.php');
$setting = include_once(__DIR__.'/setting.php');
$config->merge($db);
$config->merge($redis);
$config->merge($cachekeys);
$config->merge($setting);
return $config;
