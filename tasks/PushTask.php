<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-4
 * Time: 下午5:09
 */

class PushTask extends \Phalcon\CLI\Task {

    public function runAction() {
        $this->_processQueue();
    }

    private function _processQueue() {
        $key = \Util\ReadConfig::get('queue_keys.pushfeeds', $this->getDI());
        $queue = \Util\BStalkClient::getInstance($this->getDI());
        $queue->choose($key);
        $queue->watch($key);

        $userRelation = new UserRelationModel($this->getDI());
        $feedRelation = new FeedRelationModel($this->getDI());
        $countRelation =  new UserCountModel($this->getDI());

        while(($job = $queue->peekReady()) !== false) {
            $data = $job->getBody();
            list($app_id, $uid, $feed_id) = explode('|', $data);

            if($countRelation->isBigv($uid)) {
                $job->delete();
                continue;
            }

            $page = 1;
            $offset = 0;
            $count = 101;
            while($results = $userRelation->getFansList($uid, $offset, $count)) {
                $offset = ($page - 1) * $count - 1;
                foreach($results as $result) {
                    $feedRelation->create(array(
                        'app_id'=>$app_id,
                        'uid'=>$result['friend_id'],
                        'feed_id'=>$feed_id,
                        'create_at'=>time(),
                    ));
                }
                if(count($results) <= $count) {
                    break;
                }
            }
            $job->delete();
        }
    }
}