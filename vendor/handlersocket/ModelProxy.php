<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-27
 * Time: 下午5:06
 *
 * $proxy = new \HSocket\ModelProxy($app->getDI()->get('config'), 'test');
$model = $proxy->getHandlerSocketModel(\HSocket\Config\IConfig::READ_PORT);

$model->connect(\HSocket\Model::SELECT, 'user', array('name','email'));
$result = $model->find(1);
var_dump($result);
 *
 *
 */

namespace HSocket;

use HSocket\Model;
use HSocket\Config\IConfig;
use HSocket\Config\PhalconConfigAdapter;
use HSocket\ModelException;

class ModelProxy {

    public $link = '';

    private $configProxy = null;

    public function __construct($config, $link='', $prefix='db') {
        $this->link = $link;
        $this->configProxy = new PhalconConfigAdapter($prefix, $config);
    }

    public function getHandlerSocketModel($port=IConfig::WRITE_PORT, $assign='') {
        $config = $this->configProxy->buildConfig($this->link, $port, $assign);
        $model = new Model($config);
        return $model;
    }

}