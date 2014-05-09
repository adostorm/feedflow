<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task {

    public function test0Action() {

        $data = array(
            'app_id'=>1,
            'source_id'=>1,
            'object_type'=> 1,
            'object_id'=>1,
            'author_id'=>1,
            'author'=>1,
            'centent'=> 1,
            'create_at'=>1,
            'attachment'=>1,
            'extends'=>1,
        );

        $feedModle = new FeedModel($this->getDI());
        $feed_id = $feedModle->create($data);
        var_dump($feed_id);



    }

}