<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task
{

    public function test0Action()
    {
        $data = array(
            "app_id" => 1,
            "source_id" => 1,
            "object_type" => 2,
            "object_id" => 1341918,
            "author_id" => 8048111,
            "author" => "xjay",
            "content" => "测试发帖。。。",
            "create_at" => 1376712770,
            "attachment" => "",
            "extends" => array(
                'fid' => 1,
                'ishelp' => 1,
                'groupname' => '爱相约',
            ),
        );
        $feed = new FeedModel($this->getDI());
        $r = $feed->create($data);
    }

    public function test1Action() {
        $userFeed = new UserFeed();
        $result = $userFeed->getListByAppIdAndUid(1, 1);
        var_dump($result);
    }

}