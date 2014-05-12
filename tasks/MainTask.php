<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task {

    public function test0Action() {

        $a = new UserRelationModel($this->getDI());
        $results = $a->getFansList(1,0,15);
        var_dump($results);


        exit;
        $defaults = array(
            'persistent' => true,
            'host' => '127.0.0.1',
            'port' => 11980,
            'timeout' => 1
        );
        $bs = new \Util\BStalkd($defaults);
var_dump($bs);

        exit;
        $count = new UserCountModel($this->getDi());
        $count->updateCount(1, 'feed_count', 1, true);



        exit;
        $hs = new HandlerSocket('127.0.0.1', 9999);

        $data = array(
            'uid'=>time(),
            'feed_count'=>1,
        );

        $hs->openIndex(3, 'db_countstate', 'user_count_0', 'PRIMARY', array_keys($data));
        $d  = $hs->executeSingle(3, '=', array(1), 1, 0, '+', array_values($data));
        var_dump($d);
        if($d ===0) {
            $hs->openIndex(3, 'db_countstate', 'user_count_0', 'PRIMARY', array_keys($data));
            $rs = $hs->executeInsert(3, array_values($data));
        }





//        for($i=0; $i<100; $i++) {
//            $data = array(
//                'uid'=>time() + $i,
//                'feed_count'=>1,
//            );
//            $hs->openIndex(3, 'db_countstate', 'user_count_0', 'PRIMARY', array_keys($data));
//            $rs = $hs->executeInsert(3, array_values($data));
//            var_dump($rs, $hs->getError());
//
//
//            $data = array(
//                'id'=>time() + $i,
//                'tid'=>1,
//            );
//            $hs->openIndex(3, 'test', 'feed', 'PRIMARY', array_keys($data));
//            $rs = $hs->executeInsert(3, array_values($data));
//            var_dump($rs, $hs->getError());
//        }

    }

}
