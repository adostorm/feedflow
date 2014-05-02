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
    'Redisc'=>$config->application->vendorDir.'redis/',
    'Util'=>$config->application->libraryDir,
    'HsMysql'=>$config->application->vendorDir.'hsmysql/',
));

$loader->register();
