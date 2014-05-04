<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-2
 * Time: 下午4:14
 */

class FeedRelationModel extends \HsMysql\Model
{

    public $dbname = 'feedstate';

    public $tbname = 'feed_relation';

    public $index = 'idx0';

    public function getListByUid($uid, $offset, $limit)
    {
        $result = $this->limit($offset, $limit)->find($uid);
        return $result;
    }

    public function create($model)
    {
        $result = $this->insert(array(
            'uid' => (int) $model['uid'],
            'friend_uid' => (int) $model['friend_uid'],
            'feed_id' => (int) $model['feed_id'],
        ));
        return $result;
    }

}