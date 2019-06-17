<?php
namespace QSCI\TaskProviders;

class ProviderLoad{
    private static $providerMap;

    public static function import(){
        $providers = glob(__DIR__ . '/*Provider/*.php');
        foreach($providers as $provider){
            $fileName = basename($provider);
            $fileName = str_replace('.php', '', $fileName);
            $pathArr = explode('/', $provider);
            $providerName = $pathArr[count($pathArr) - 2];
            $className = "\QSCI\TaskProviders\\{$providerName}\\{$fileName}";
            $instance = new $className;
            if($instance instanceof Provider){
                self::$providerMap[$instance->sign()] = $instance;
            }
        }
    }

    public static function getInstance($sign){
        $instance = self::$providerMap[$sign];
        return $instance;
    }
}