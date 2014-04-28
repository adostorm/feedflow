<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:50
 */


return new \Phalcon\Config(array(
    'redis'=>array(
        'link_master0'=>array(
            'host'=>'127.0.0.1',
            'port'=>6379,
        ),
        'link_slave0'=>array(
            'host'=>'127.0.0.1',
            'port'=>6390,
        ),
        'link_slave1'=>array(
            'host'=>'127.0.0.1',
            'port'=>6391,
        ),
    ),
));