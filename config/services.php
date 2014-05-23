<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;


$di = new FactoryDefault();

/**
 * Sets the view component
 */
$di['view'] = function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);

    return $view;
};

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di['url'] = function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
};

foreach ($config as $k => $v) {
    if (0 === stripos($k, 'link_')) {
        if(isset($v->slaves)) {
            $slaves = $v->slaves->toArray();
            if($slaves) {
                foreach($slaves as $i=>$slave) {
                    echo sprintf('%s_read_%d', $k, $i);echo PHP_EOL;
                    $di->set(sprintf('%s_read_%d', $k, $i), function () use ($slave) {
                        return new DbAdapter($slave);
                    });
                }

            }
        }
        $di->set($k, function () use ($v) {
            return new DbAdapter($v);
        });
    }
}

$di['config'] = function () use ($config) {
    return $config;
};