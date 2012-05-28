<?php

define('HAWKEYE_ROOT', realpath(dirname(dirname(__FILE__))));

$files = array(
    'HawkeyeDB.php',
    'PluginAbstract.php',
    'Exception.php',
);

foreach ($files as $file) {
    require HAWKEYE_ROOT .
             DIRECTORY_SEPARATOR .
             'lib' .
             DIRECTORY_SEPARATOR .
             $file;
}

