<?php

use Phalcon\DI\FactoryDefault\CLI as CliDI,
    Phalcon\CLI\Console as ConsoleApp,
    Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;

define('VERSION', '1.0.0');

//Using the CLI factory default services container
$di = new CliDI();

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__)));

/**
 * Register the autoloader and tell it to register the tasks directory
 */
$loader = new \Phalcon\Loader();

//Register some namespaces

$loader->registerDirs(
    array(
        APPLICATION_PATH . '/tasks',
        APPLICATION_PATH . '/models',
        APPLICATION_PATH . '/models_hs',
        APPLICATION_PATH . '/vendor',
    )
);

$loader->registerNamespaces(array(
    'Util' => APPLICATION_PATH . '/library/',
    'HsMysql' => APPLICATION_PATH . '/vendor/hsmysql/',
));

$loader->register();

// Load the configuration file (if any)
if (is_readable(APPLICATION_PATH . '/config/config.php')) {
    $config = include APPLICATION_PATH . '/config/config.php';
    $di->set('config', $config);

    foreach ($config as $k => $v) {
        if (0 === stripos($k, 'link_')) {
            if(isset($v->slaves)) {
                $slaves = $v->slaves->toArray();
                if($slaves) {
                    foreach($slaves as $i=>$slave) {
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
}

//var_dump($di);

//Create a console application
$console = new ConsoleApp();
$console->setDI($di);

/**
 * Process the console arguments
 */
$arguments = array();
foreach ($argv as $k => $arg) {
    if ($k == 1) {
        $arguments['task'] = $arg;
    } elseif ($k == 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

// define global constants for the current task and action
define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

try {
    // handle incoming arguments
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    echo $e->getMessage();
    exit(255);
}