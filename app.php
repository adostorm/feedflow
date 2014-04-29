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

$userController = new UserController();
/**
 *
 */
$app->post('/friendships/create', function () use ($app) {
    $app_id = $app->request->getPost('app_id');
    $repush = $app->request->getPost('repush');
    $uid = $app->request->getPost('uid');
    $friend_uid = $app->request->getPost('friend_uid');

    $user = new UserModel();
    $status = $user->checkRelation($uid, $friend_uid);

    $result = $user->createRelation($uid, $friend_uid, $status);

    var_dump($status, $result);

    //内部推送动态
    if($app_id&&($status==-99 || $repush)) {
        $feed = new FeedController();
        $feed->create();
    }

});


///////Test
$app->get('/test/1', function () use ($app) {
    $log = new \HSocket\ModelLog($app->getDI()->get('config'));
    $log->error('vvv');
});

$app->get('/test/2', function () use ($app) {
    $conf = \Util\ReadConfig::get('application', $app->getDI()->get('config'));
    echo \Util\ReadConfig::get('path', $conf);
});

$app->get('/test/3',function () use ($app) {
//    $proxy = new \HSocket\ModelProxy($app->getDI()->get('name'));
//    $model =$proxy->getHandlerSocketModel(\HSocket\Config\IConfig::WRITE_PORT);
//
//    $index = $model->createIndex(\HSocket\Model::SELECT, 'fans', '', array('uid', 'friend_uid', 'status'), array('friend_uid'));
//    $index->find(array('='=>''));

//    $ref =

});





