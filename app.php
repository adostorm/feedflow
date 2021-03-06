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
    echo "<h1>404 NOT FOUND</h1>";
});

//$app->before(function () use ($app) {
//    \Util\TokenProof::check($app, \Util\ReadConfig::get('api_key', $app->getDI()));
//});

$feedController = new FeedController();
/**
 * test case :
 * curl -d "app_id=1&source_id=1&object_type=1&object_id=121212&author_id=1231&author=塑料袋&content=aaaaaaaaaa&create_at=12121231" http://feed.api.test.com/statuses/create
 */
$app->post('/statuses/create', array($feedController, 'create'));

/**
 * curl -i -X GET 'http://feed.api.test.com/statuses/public_timeline?app_id=1'
 */
$app->get('/statuses/public_timeline', array($feedController, 'getFeedListByAppId'));

/**
 * curl -i -X GET 'http://feed.api.test.com/statuses/friends_timeline?app_id=1&uid=1'
 */
$app->get('/statuses/friends_timeline', array($feedController, 'getFeedListByUid'));

$userController = new UserController();

/**
 * curl -d "uid=1&friend_uid=2" 'http://feed.api.test.com/friendships/create'
 * curl -d "uid=2&friend_uid=1" 'http://feed.api.test.com/friendships/create'
 */
$app->post('/friendships/create', array($userController, 'addFollow'));

/**
 * curl -d "uid=1&friend_uid=2" 'http://feed.api.test.com/friendships/destroy'
 */
$app->post('/friendships/destroy', array($userController, 'unFollow'));

/**
 * curl -i -X GET 'http://feed.api.test.com/friendships/followers?uid=1'
 */
$app->get('/friendships/followers', array($userController, 'getFansList'));

/**
 * curl -i -X GET 'http://feed.api.test.com/friendships/friends?uid=2'
 */
$app->get('/friendships/friends', array($userController, 'getFollowList'));

$app->get('/friendships/statuses', array($userController, 'getRelations'));

/**
 * curl -i -X GET 'http://feed.api.test.com/users/counts?uids=1'
 */
$app->get('/users/counts', array($userController, 'getCounts'));

/**
 * curl -i -X GET 'http://feed.api.test.com/remind/feed_count?uid=1231'
 */
$app->get('/remind/feed_count', array($userController, 'getFeedCount'));