<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-9
 * Time: 下午3:52
 */

class FeedIndexModel extends \HsMysql\Model
{

    /**
     * 数据库名称
     * @var string
     */
    public $dbname = 'db_countstate';

    /**
     * 表名称
     * @var string
     */
    public $tbname = 'feed_index';

    /**
     * 主键
     * @var string
     */
    public $index = 'PRIMARY';

    /**
     * 创建Feed索引，也就是主键的自增ID
     * @return mixed
     */
    public function create()
    {
        return $this->insert(array(
            'id' => null,
        ));
    }

}