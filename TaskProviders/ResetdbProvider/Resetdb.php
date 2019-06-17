<?php
namespace QSCI\TaskProviders\ResetdbProvider;

use Exception;
use QSCI\Task;
use QSCI\TaskManager;
use QSCI\TaskProviders\Provider;
use Swoole\Http\Request;
use Swoole\Process;

class Resetdb implements Provider {

    public function sign() : string
    {
        return 'resetdb';
    }

    public function init(Request $request, TaskManager $manager){
        $project_name = $request->post['project'];

        if(!preg_match("/^[A-Za-z0-9_\/\-]+$/", $project_name)){
            throw new Exception('invalid project name');
        }

        $manager->addTask(new Task(function (Process $worker) use($project_name){
            $worker->exec('/usr/local/bin/php',["/app/{$project_name}/artisan", "migrate:refresh", "--seed", "--force"]);
        }));
    }
}