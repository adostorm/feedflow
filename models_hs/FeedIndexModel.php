<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-9
 * Time: 下午3:52
 */

class FeedIndexModel extends CommonModel
{

    protected $DI = null;

    protected $dbLink = 'link_db_countstate';

    protected $tbSuffix = 'feed_index';

    protected $primary = 'PRIMARY';

    public function __construct($DI) {
        $this->DI = $DI;
    }

    /**
     * 创建Feed索引，也就是主键的自增ID
     * @return mixed
     */
    public function create()
    {
        $model = $this->getModel();
        $result =  $model->insert(array(
            'id' => null,
        ));
        return $result;
    }

}