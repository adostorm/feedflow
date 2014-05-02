<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-30
 * Time: 下午5:04
 */

class FeedModel extends \HsMysql\Model {

    public $dbname = 'feed';

    public $tbname = 'feed_content_1';

    public $index = 'primary';

    public function create($model) {
        $result = $this->insert(array(
            'app_id'=>(int) $model->app_id,
            'source_id'=>(int) $model->source_id,
            'object_type'=> $model->object_type,
            'object_id'=>(int) $model->object_id,
            'author_id'=>(int) $model->author_id,
            'centent'=> $model->centent,
            'create_at'=>(int) $model->create_at,
        ));
        return $result;
    }

    public function getById($feed_id) {
        return $this->find($feed_id);
    }


}