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

        $userModel = new UserRelationModel($this->getDI());
        $feedModel = new FeedRelationModel($this->getDI());
        $countModel =  new UserCountModel($this->getDI());

        $big_v_level = \Util\ReadConfig::get('setting.big_v_level', $this->getDI());

        while(($job = $queue->peekReady()) !== false) {
            $data = $job->getBody();
            list($uid, $feed_id) = explode('|', $data);

            $countData = $countModel->getCountByUid($uid);
            if(isset($countData['fans_count']) && $countData['fans_count'] >= $big_v_level) {
                $job->delete();
                continue;
            }

            $page = 1;
            $offset = 0;
            $count = 101;
            while($results = $userModel->getFansList($uid, $offset, $count)) {
                $offset = ($page - 1) * $count - 1;
                foreach($results as $result) {
                    $feedModel->create(array(
                        'uid'=>$result['friend_id'],
                        'friend_uid'=>$uid,
                        'feed_id'=>$feed_id,
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