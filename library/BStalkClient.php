<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-3
 * Time: 上午3:22
 */

namespace Util;

use Phalcon\Queue\Beanstalk as CCBeanstalk;

final class BStalkClient
{
    private $host = '';

    private $port = 0;

    public static $cacheBeans = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance($di, $queueName = 'link_queue0')
    {
        if (!isset(self::$cacheBeans[$queueName])) {
            $self = new self;
            $self->host = ReadConfig::get(sprintf('beanstalk.%s.host', $queueName), $di);
            $self->port = ReadConfig::get(sprintf('beanstalk.%s.port', $queueName), $di);
            self::$cacheBeans[$queueName] = $self->_init();
        }
        return self::$cacheBeans[$queueName];
    }

    private function _init()
    {
        try {
            return new CCBeanstalk(array(
                'host' => $this->host,
                'port' => $this->port
            ));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}