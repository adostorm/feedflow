<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-3
 * Time: 上午3:22
 */

namespace Util;

use Phalcon\Queue\Beanstalk;

class BStalkClient {

    private $host = '';

    private $port = 0;

    private static $bean = null;

    private function __construct() {}
    private function __clone(){}

    public static function getInstance($di) {
        if(null === self::$bean) {
            $self = new self;
            $self->host = ReadConfig::get('queue_connect.host', $di);
            $self->port = ReadConfig::get('queue_connect.port', $di);
            self::$bean = $self->_init();
        }
        return self::$bean;
    }

    private function _init() {
        try {
            $bean = new Beanstalk(array(
                'host'=>$this->host,
                'port'=>$this->port
            ));
            $bean->connect();
            return $bean;
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

}