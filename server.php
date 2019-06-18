<?php
use Swoole\Process;
use QSCI\TaskManager;
use QSCI\Pool;
use QSCI\TaskProviders\ProviderLoad;

include "autoload.php";

const TOKEN = '设置你自己的token';

const ERROR_PREFIX = "ERROR*****";
const ERROR_SUFFIX= "*****ERROR";
const PORT = 1802;

$http = new swoole_http_server("0.0.0.0", PORT);

$http->set([
    //关闭内置协程
    'enable_coroutine' => false,
    'worker_num' => 1
]);

$http->on('WorkerStart', function($serv){
    ProviderLoad::import();
});

$http->on('connect', function($serv){
    Pool::clear($serv);
});

$http->on('request', function ($request, $response) {
    try{
        if($request->get['token'] != TOKEN){
            throw new Exception('invalid token');
        }
        $sign = $request->get['action'];

        $manager = new TaskManager($response);

        Pool::addTaskManager($manager);

        $provider = ProviderLoad::getInstance($sign);
        $provider->init($request, $manager);
        $manager->asynRun();

    }
    catch(Exception $ex){
        $response->status(502);
        $response->end($ex->getMessage());
    }

});

$http->start();
