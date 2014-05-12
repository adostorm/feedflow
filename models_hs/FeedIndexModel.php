<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-9
 * Time: 下午3:52
 */

class FeedIndexModel extends \HsMysql\Model {

    public $dbname = 'db_countstate';

    public $tbname = 'feed_index';

    public $index = 'PRIMARY';

    public function create() {
        return $this->insert(array(
            'id'=>null,
        ));
    }

}