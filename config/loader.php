<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();

$loader->registerDirs(
    array(
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->models_hsDir,
    )
);

$loader->registerNamespaces(array(
    'Util' => $config->application->libraryDir,
    'HsMysql' => $config->application->vendorDir . 'hsmysql/',
));

$loader->register();
