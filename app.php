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

$app->post('/feed/create', function () use ($app) {
    $mode = $app->request->getPost('mode');
    $msg = $app->request->getPost('msg');

    $queue = new \Phalcon\Queue\Beanstalk(array(
        'host'=>'127.0.0.1',
        'port'=>11307
    ));
    $queue->connect();
    $queue->choose('bean:queue:feed');

    if($mode=='multi') {
        $msgArray = msgpack_unpack($msg);
        foreach($msgArray as $row) {
            $queue->put(msgpack_pack($row));
        }
    } else {
        $queue->put($msg);
    }

    $queue->disconnect();

    echo json_encode(array(
        'status'=>1,
        'msg'=>'Ok.'
    ));
});

$app->get('/statuses/public_timeline', array('FeedController', 'getFeedByAppId'));



