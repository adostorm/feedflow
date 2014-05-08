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

$feedController = new FeedController();
$app->post('/feed/create', array($feedController, 'create'));
$app->get('/statuses/public_timeline', array($feedController, 'getFeedByAppId'));
$app->get('/statuses/friends_timeline', array($feedController, 'getFeedByUid'));

$userController = new UserController();
$app->post('/friendships/create', array($userController, 'addFollow'));
$app->post('/friendships/destroy', array($userController, 'unFollow'));
$app->get('/friendships/followers', array($userController, 'getFansList'));
$app->get('/friendships/friends', array($userController, 'getFollowList'));


