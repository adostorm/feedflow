<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

use Phalcon\Queue\Beanstalk;

class MainTask extends \Phalcon\CLI\Task {

    public function test0Action() {

        $config = array(
            'host'=>'127.0.0.1',
            'port'=>11980,
        );
        $k1 = 'aaaaa';
        $k2 = 'bbbbb';

//        $b = new Beanstalk($config);//Test::getInstance($this->getDI());
//        for($i=0; $i<100000; $i++) {
//            $b->choose($k1);
//            $b->put($i);
//        }
//        $b->disconnect();

        $b1 = new Beanstalk($config);
        $b1->choose($k1);
        $b1->watch($k1);

        $b2 = new Beanstalk($config);
        $b2->choose($k2);
        $b2->watch($k2);
        while(($job = $b1->peekReady()) !== false) {
            $msg  = $job->getBody();
            var_dump($msg);
            $b2->put($msg);
            $job->delete();
        }
        $b->disconnect();

//        $bc = $b = new Beanstalk($config);//Test::getInstance($this->getDI());
//        $b->choose($k1);
//        $b->watch($k1);
//        while(($job = $b->peekReady()) !== false) {
//            $msg  = $job->getBody();
//            var_dump($msg);
//            $job->delete();
//        }
//
//        $b->choose($k2);
//        $b->watch($k2);
//        while(($job = $b->peekReady()) !== false) {
//            $msg  = $job->getBody();
//            var_dump($msg);
//            $job->delete();
//        }
//        $b->disconnect();
    }

}


class Test {

    private static $cacheConfigs = array();

    public static function getInstance($di, $queueName='link_queue0') {
        if(!isset(self::$cacheConfigs[$queueName])) {
            $config = array(
                'host'=>\Util\ReadConfig::get(sprintf('beanstalk.%s.host', $queueName), $di),
                'port'=>\Util\ReadConfig::get(sprintf('beanstalk.%s.port', $queueName), $di),
            );
            self::$cacheConfigs[$queueName] = $config;
            unset($config);
        }
        return new Beanstalk(self::$cacheConfigs[$queueName]);
    }

}