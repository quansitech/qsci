<?php
namespace QSCI\TaskProviders\AutotestProvider;

use Exception;
use QSCI\Task;
use QSCI\TaskManager;
use QSCI\TaskProviders\Provider;
use Swoole\Http\Request;
use Swoole\Process;

class Autotest implements Provider{

    public function sign() : string
    {
        return 'autotest';
    }

    public function init(Request $request, TaskManager $manager){
        $project_name = $request->post['project'];

        if(!preg_match("/^[A-Za-z0-9_\/\-]+$/", $project_name)){
            throw new Exception('invalid project name');
        }

        $manager->addTask(new Task(function (Process $worker) use($project_name){
            $worker->exec("/app/{$project_name}/vendor/bin/phpunit",["--configuration", "/app/{$project_name}/phpunit.xml" , "/app/{$project_name}/lara/tests"]);
        }));
    }
}