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
$app->get('/feed/public_timeline', array($feedController, 'getFeedByAppId'));
/**
 * 用户关注人的动态
 */
$app->get('/feed/user_timeline', array($feedController, 'getFeedByUid'));

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

$app->get('/friendships/followers', function () use ($app) {
    $pageDefault = 1;
    $countDefault = 10;

    $uid = $app->request->get('uid');
    $page = $app->request->get('page', 'int', $pageDefault);
    $count = $app->request->get('count', 'int', $countDefault);

    $page = $page > 0 ? $page : $pageDefault;
    $count = $count > 0 && $count <= 50 ? $count : $countDefault;

    $offset = ($page - 1) * $count;

    $user = new UserModel();
    $result = $user->getFansList($uid, $count, $offset);

    var_dump($result);
});

$app->get('/friendships/friends', function () use ($app) {
    $pageDefault = 1;
    $countDefault = 10;

    $uid = $app->request->get('uid');
    $page = $app->request->get('page', 'int', $pageDefault);
    $count = $app->request->get('count', 'int', $countDefault);

    $page = $page > 0 ? $page : $pageDefault;
    $count = $count > 0 && $count <= 50 ? $count : $countDefault;

    $offset = ($page - 1) * $count;

    $user = new UserModel();
    $result = $user->getFollowList($uid, $count, $offset);

    var_dump($result);
});

$app->post('/friendships/destroy', function () use ($app) {
    $uid = $app->request->getPost('uid');
    $friend_uid = $app->request->getPost('friend_uid');

    $user = new UserModel();
    $result = $user->removeRelation($uid, $friend_uid);


    var_dump($result);
});


///////Test
$app->get('/test/1',function () use ($app) {
    try {
        $hs = new \HandlerSocket('localhost', 9999);
        $hs->auth('5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1');

        $uid = "1";
        $friend_uid = "2";

        $hs->openIndex(\HSocket\Model::SELECT, 'userstate', 'user_relation', 'idx0',
            array('uid','friend_uid','status','create_at'),array('status'));

        $result = $hs->executeSingle(\HSocket\Model::SELECT, '=', array($uid), 10, 0, null, null, array('F', '>', 0, 0));

        var_dump($result);

    } catch(\HandlerSocketException $e) {
        echo $e->getMessage();
    } catch(\Phalcon\Exception $e) {
        echo $e->getMessage();
    }

});





