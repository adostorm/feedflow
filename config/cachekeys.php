<?php
/**
 * User: Jerry.Lee
 * Date: 14-4-28
 * Time: 下午5:55
 */


return new \Phalcon\Config(array(
    'cachekeys_redis'=>array(
        'appfeeds'=>'app:%d:feeds', # 根据APP id查询全站的动态
    ),
    'queuekeys_beans'=>array(
        'allfeeds0'=>'allfeeds0', #所有应用的动态 队列0
        'allfeeds1'=>'allfeeds1',
    ),
));