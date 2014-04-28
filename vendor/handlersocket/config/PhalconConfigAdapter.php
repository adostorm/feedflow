<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-27
 * Time: 下午5:20
 */

namespace HSocket\Config;

use HSocket\Config\IConfig;
use HSocket\ModelException;

class PhalconConfigAdapter implements IConfig
{

    private $prefix = '';
    private $diConfig = null;

    private $masterLinks = array();
    private $slaveLinks = array();

    /**
     * @return array
     */
    public function getMasterLinks()
    {
        return $this->masterLinks;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return array
     */
    public function getSlaveLinks()
    {
        return $this->slaveLinks;
    }

    public function __construct($prefix, $diConfig)
    {
        $this->prefix = $prefix;
        $this->diConfig = $diConfig;
    }

    public function buildConfig($link, $port = self::WRITE_PORT, $assign='')
    {
        $configs = $this->selectConfig($link, $assign);

        if(!empty($assign)) {
            $index = $assign;
            if($port == self::WRITE_PORT && false !== stripos($index, 'slave')) {
                throw new ModelException(sprintf('db link "%s" can not be writed port', $index));
                exit(1);
            }
        } else if ($port == self::WRITE_PORT || empty($this->slaveLinks)) {
            $index = $this->masterLinks[array_rand($this->masterLinks)];
        } else if($port == self::READ_PORT) {
            $index = $this->slaveLinks[array_rand($this->slaveLinks)];
        }

        if(!isset($configs[$index])) {
            throw new ModelException(sprintf('no db link : %S', $index));
            exit(1);
        }

        $config = $configs[$index];

        if($port == self::WRITE_PORT) {
            $_point = 'hs_write_port';
        } else if($port == self::READ_PORT) {
            $_point =  'hs_read_port';
        }
        $config['port'] = $config->{$_point};

        return $config;
    }

    private function selectConfig($link, $assign)
    {
        static $configs = null;

        if (null == $configs) {
            $_prefix = $this->prefix;

            if(!isset($this->diConfig->{$_prefix})) {
                throw new ModelException(sprintf('no db key : %s ... happen at %s',$_prefix.PHP_EOL, __METHOD__.PHP_EOL.__LINE__));
            } else if(!isset($this->diConfig->{$_prefix}->{$link})) {
                throw new ModelException(sprintf('no db link : %s ... happen at %s', $link.PHP_EOL, __METHOD__.PHP_EOL.__LINE__));
            }

            $configs = $this->diConfig->{$_prefix}->{$link};

            if(!empty($assign)) {

            } else {
                $masterLinks = array();
                $slaveLinks = array();
                foreach ($configs as $k => $v) {
                    0 === stripos($k, 'master') ? $masterLinks[] = $k : 0;
                    0 === stripos($k, 'slave') ? $slaveLinks[] = $k : 0;
                }

                if(empty($masterLinks)) {
                    throw new ModelException('no db master');
                    exit(1);
                }

                $this->masterLinks = $masterLinks;
                $this->slaveLinks = $slaveLinks;
            }
        }

        return $configs;
    }

}