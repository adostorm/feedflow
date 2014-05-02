<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:03
 */

class UserCountModel extends \HsMysql\Model {

    public $dbname = 'userstate';

    public $tbname = 'user_count';

    public $index = 'primary';

    public function getCountByUid($uid) {
        $result = $this->field('uid,follow_count,fans_count,feed_count')->find($uid);
        return $result;
    }

    public function updateCount($uid, $field='', $num='') {
        $result = $this->update($uid, array(
            $field=>$num
        ), '+');
        return $result;
    }


}