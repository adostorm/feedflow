<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task {

    public function test1Action() {
        $model = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            'host'=>\Util\ReadConfig::get('link_userstate.host', $this->getDI()),
            'password'=>\Util\ReadConfig::get('link_userstate.password', $this->getDI()),
            'username'=>\Util\ReadConfig::get('link_userstate.username', $this->getDI()),
            'dbname'=>\Util\ReadConfig::get('link_userstate.dbname', $this->getDI()),
        ));
        $model->query('set names utf8');
        $result = $model->fetchAll('select * from `user_relation` where uid=1 order by `create_at` desc', \Phalcon\Db::FETCH_ASSOC);
        var_dump($result);

    }

    public function test2Action() {
        $user = new UserRelationModel($this->getDI());
        for($i=2;$i<4;$i++) {
            $user->createRelation(1,$i);
        }
    }

}