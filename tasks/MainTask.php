<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */


use Phalcon\Queue\Beanstalk as BBeanstalk;

class MainTask extends \Phalcon\CLI\Task {

    public function test0Action() {
        $f =  new FeedModel($this->getDI());
        $d =  $f->getById(1);
        var_dump($d,'----');
    }

    public function test1Action() {
        $bean = Beanstalk::init(); // returns BeanstalkPool instance
        $bean->addServer('localhost', 11980);
        $bean->useTube('my-tube');
        $bean->put('Hello World!');
    }


    public function test2Action() {
        $key1 = 'aaaaa';
        $key2 = 'bbbbb';
        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);
        $beans->choose($key1);
        $beans->put('11111111');


        $beans->choose($key2);
        $beans->put('2222');

        $beans->disconnect();

        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);

        $beans->choose($key1);
        $beans->watch($key1);
        while ($beans->peekReady() !== false) {
            $job = $beans->reserve();
            $message = $job->getBody();
            var_dump($message);
            $job->delete();
        }



        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);
        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);
        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);
        $beans = \Util\BStalkClient::getInstance($this->getDI());
        var_dump($beans);


        exit;



        $beans = new BBeanstalk(array(
            'host'=>'127.0.0.1',
            'port'=>11980
        ));


        $beans = \Util\BStalkClient::getInstance($this->getDI());
        $beans->choose($key1);
        $beans->put(msgpack_pack(array(
            'id'=>1,
            'tid'=>2,
            'app_id'=>3,
        )));

        $beans->choose($key2);
        $beans->put('ssssssssss');
        $beans->disconnect();
////////////////////////////////

        $beans = new BBeanstalk(array(
            'host'=>'127.0.0.1',
            'port'=>11980
        ));

        var_dump($beans);
        $beans = \Util\BStalkClient::getInstance($this->getDI());


        var_dump($beans);
        $beans->choose($key1);
        $beans->watch($key1);
        while ($beans->peekReady() !== false) {
            $job = $beans->reserve();
            $message = $job->getBody();
            var_dump($message);
            $job->delete();
        }

        $beans->choose($key2);
        $beans->watch($key2);
        while ($beans->peekReady() !== false) {
            $job = $beans->reserve();
            $message = $job->getBody();
            var_dump($message);
            $job->delete();
        }

        $beans->disconnect();

        exit;


        $queue = new \Phalcon\Queue\Beanstalk(array(
            'host' => '127.0.0.1',
            'port'=>'11980',
        ));
        $queue->choose('tube1:fff');
        $r = $queue->put(array('processVideo' => 4871));
        $queue->choose('tube2:11:FFF');
        $r = $queue->put(array('1212' => 32423));
        $queue->disconnect();







        $queue = new \Phalcon\Queue\Beanstalk(array(
            'host' => '127.0.0.1',
            'port'=>'11980',
        ));
        $queue->choose('tube1:fff');
        $queue->watch('tube1:fff');
        while ($queue->peekReady() !== false) {
            $job = $queue->reserve();

            $message = $job->getBody();
            // do something
            var_dump($message);

            $job->delete();
        }



        $queue->choose('tube2:11:FFF');
        $queue->watch('tube2:11:FFF');
        while ($queue->peekReady() !== false) {
            $job = $queue->reserve();

            $message = $job->getBody();
            // do something
            var_dump($message);

            $job->delete();
        }

        $queue->disconnect();
    }

}
