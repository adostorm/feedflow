<?php

/**
 * Add your routes here
 */
$app->get('/', function () use ($app) {
    echo $app['view']->getRender(null, 'index');
});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo $app['view']->getRender(null, '404');
});


$feedController = new FeedController();
/**
 * Feed队列，交由/app/tasks/FeedTask.php 处理
 */
$app->post('/feed/create', array($feedController, 'create'));
/**
 * 全站动态，根据app_id获取Redis的内容
 */
$app->get('/timeline/public', array($feedController, 'getFeedByAppId'));
/**
 * 用户关注人的动态
 */
$app->get('/timeline/friends', array($feedController, 'getFeedByUid'));


///////Test
$app->get('/test/1', function () use ($app) {
    $log = new \HSocket\ModelLog($app->getDI()->get('config'));
    $log->error('vvv');
});

$app->get('/test/2', function () use ($app) {
    $conf = \Util\ReadConfig::get('application', $app->getDI()->get('config'));
    echo \Util\ReadConfig::get('path', $conf);
});





