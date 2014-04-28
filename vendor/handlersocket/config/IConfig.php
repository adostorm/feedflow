<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-27
 * Time: 下午5:23
 */

namespace HSocket\Config;


interface IConfig {

    const READ_PORT = 1;

    const WRITE_PORT = 2;

    /**
     * @param $link key of $config
     * @param $port Read or Write Port of HandlerSocket
     * @return mixed
     *
     *  $port = IConfig::READ_PORT
     *  $port = IConfig::WRITE_PORT
     */
    public function buildConfig($link, $port);

}