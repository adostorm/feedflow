<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-12
 * Time: 下午2:07
 */

class UserFeedModel extends \HsMysql\Model {

    public $dbname = 'db_userfeed';

    public $tbname = 'user_feed';

    public $index = 'idx0';

    public $partition = array(
        'field'=>'uid',
        'mode'=>'range',
        'step'=>array(1,1000000,2000000,3000000,4000000,5000000,
            6000000,7000000,8000000,9000000,10000000,11000000,12000000,
            13000000,14000000,15000000,16000000,17000000,1000000000),
        'limit'=>399
    );

    public function __construct($di) {
        parent::__construct($di, '');
    }

    public function create($data) {
        return $this->insert(array(
            'app_id'=>(int) $data['app_id'],
            'uid'=> (int) $data['uid'],
            'feed_id'=> (int) $data['feed_id'],
            'create_at'=>(int) $data['create_at'],
        ));
    }

}