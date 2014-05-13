<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-24
 * Time: 下午6:38
 */

class MainTask extends \Phalcon\CLI\Task
{
    public function t0Action() {
        $redis = \Util\RedisClient::getInstance($this->getDI());
        $redis->sfs();
    }
}
