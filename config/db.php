<?php

/**
 * User: JiaJia.Lee
 * Date: 14-4-27
 * Time: 下午5:37
 *
 * Description :
 *  Database Config
 */

return new \Phalcon\Config(array(
    'db'=>array(
        'test' => array(
            'master' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'test',
                'hs_read_port' => 9998,
                'hs_write_port' => 9999,
            ),
            'slave0' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'test',
                'hs_read_port' => 9998,
            ),
        ),

        'link_user_relation' => array(
            'master' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'userstate',
                'hs_read_port' => 9998,
                'hs_write_port' => 9999,
            ),
        ),

        'feed_relation' => array(
            'master' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'test',
                'hs_read_port' => 9998,
                'hs_write_port' => 9999,
            ),
            'slave0' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'test',
                'hs_read_port' => 9998,
            ),
        ),

        'link_feed_content' => array(
            'master' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'feed',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'feed',
                'hs_read_port' => 9998,
                'hs_write_port' => 9999,
            ),
            'slave0' => array(
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'feed',
                'password' => '5nwD14yN$kmkbmi2CfZSnlD2UeSAqx1',
                'dbname' => 'feed',
                'hs_read_port' => 9998,
            ),
        ),
    ),
));