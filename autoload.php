<?php

spl_autoload_register(function($className){
    $className = ltrim($className, '\\');
    $fileName = '';
    if (($firstNsPos = stripos($className, '\\')) && ($firstName = substr($className, 0, $firstNsPos)) && $firstName == "QSCI") {

        $namespace = substr($className, $firstNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
    }
    $fileName = __DIR__ . DIRECTORY_SEPARATOR . $fileName . '.php';

    if (file_exists($fileName)) {
        require_once $fileName;

        return true;
    }

    return false;
}, true);

