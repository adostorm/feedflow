<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();

$loader->registerDirs(
    array(
        $config->application->controllersDir,
        $config->application->modelsDir,
    )
);

$loader->registerNamespaces(array(
    'HSocket'=>$config->application->vendorDir.'handlersocket/',
    'Redisc'=>$config->application->vendorDir.'redis/',
));

$loader->register();
