<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task
{

    public function test0Action() {
        $model = new \HsMysql\Model($this->getDI());
        $model->dbname = 'db_countstate';
        $model->tbname = 'user_count_0';
        $model->index = 'PRIMARY';
        $result = $model->field('uid,follow_count,fans_count,feed_count')->in(array(
            1, 2, 121
        ))->filter(array(
                array('uid', '=', 2),
            ))->find();
        var_dump($result);
    }

    public function test1Action() {
        $model = new \HsMysql\Model($this->getDI());
        $model->dbname = 'db_userstate';
        $model->tbname = 'user_relation_0';
        $model->index = 'idx0';
        $result = $model->field('friend_uid,status')->find(1);
        var_dump($result);
    }

    public function test2Action() {
        $data = array(
           "app_id"=>1,
            "source_id"=>1,
            "object_type"=>2,
            "object_id"=>1341918,
            "author_id"=>8048111,
            "author"=>"xjay",
            "content"=>"测试发帖。。。",
            "create_at"=>1376712770,
            "attachment"=>"",
            "extends"=>array('fid'=>1),
        );
        $feed = new FeedModel($this->getDI());
        $r = $feed->create($data);


//        $data = $feed->field('extends')->setPartition(8048111)->find(36);
//        var_dump(msgpack_unpack($data['extends']));

        exit;
        $u = new UserRelationModel($this->getDI());

        $result = $u->getFansList(1);
        var_dump($result);


        exit;
        $hs = new \HandlerSocket('127.0.0.1', 9998);
        $hs->auth('5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1');
        $hs->openIndex(1, 'db_userstate', 'user_relation_0', 'idx0', 'friend_uid,status');
        $result = $hs->executeSingle(1, '=', array(1), 1, 0,null,null,null,null);
        var_dump($result, $hs->getError());
    }

}