<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午5:09
 */

class PushTask extends \Phalcon\CLI\Task {

    public function runAction($num) {
        $this->_processQueue($num[0]);
    }

    private function _processQueue($num) {
        $key = sprintf(\Util\ReadConfig::get('queue_keys.pushfeeds', $this->getDI()), $num);
        $queue = \Util\BStalkClient::getInstance($this->getDI());
        $queue->choose($key);
        $queue->watch($key);

        $userRelation = new UserRelationModel($this->getDI());
        $feedRelation = new FeedRelationModel($this->getDI());
        $countRelation =  new UserCountModel($this->getDI());

        $bigv_key = \Util\ReadConfig::get('setting.big_v_level', $this->getDI());

        while(($job = $queue->peekReady()) !== false) {

            $data = $job->getBody();

$data = '1|1231|203|12121231';
            list($app_id, $uid, $feed_id, $time) = explode('|', $data);

            if($countRelation->setBigv($uid)) {
                $job->delete();
                continue;
            }
            $results = $userRelation->getFansList($uid, 0, $bigv_key);

            if($results) {
                foreach($results as $result) {
                    $feedRelation->create(array(
                        'app_id'=>$app_id,
                        'uid'=>$result['friend_uid'],
                        'feed_id'=>$feed_id,
                        'create_at'=>$time,
                    ));
                }
            }
            exit;
//            $job->delete();
        }
    }
}