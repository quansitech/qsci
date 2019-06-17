<?php
namespace QSCI\TaskProviders\GitpulltestProvider;

use Exception;
use QSCI\Task;
use QSCI\TaskManager;
use QSCI\TaskProviders\Provider;
use Swoole\Http\Request;
use Swoole\Process;

class Gitpulltest implements Provider{

    public function sign() : string
    {
        return 'gitpulltest';
    }

    public function init(Request $request, TaskManager $manager)
    {
        $project_name = $request->post['project'];

        if(!preg_match("/^[A-Za-z0-9_\/\-]+$/", $project_name)){
            throw new Exception('invalid project name');
        }

        $branch = $request->post['branch'];

        if (!preg_match("/^[A-Za-z0-9_\-@]+$/", $branch)) {
            throw new Exception('invalid branch');
        }

        $git = $request->post['git'];

        if (!preg_match("/^[A-Za-z0-9_\-@\.:\/]+$/", $git)) {
            throw new Exception('invalid git');
        }

        $manager->addTask(new Task(function (Process $worker) use ($project_name, $branch) {
            $worker->exec('/bin/sh', [__DIR__ . '/gitPull.sh', "/app/{$project_name}", $branch]);
        }, 'git pull error!', 'git pull finished!'));

        $manager->addTask(new Task(function (Process $worker) use ($project_name) {
            $dir = "/app/{$project_name}/app/Runtime";
            if (file_exists($dir)) {
                $worker->exec('/bin/rm', ["-rf", "{$dir}/*"]);
            }
        }, 'clear runtime failed!', 'clear runtime finished!'));

        $path = "/app/{$project_name}";
        $manager->addTask(new Task(function (Process $worker) use ($path) {
            $worker->exec('/bin/sh', [__DIR__ .  '/composerInstall.sh', $path]);
        }, 'composer install failed!', 'composer install finished!'));

        $manager->addTask(new Task(function (Process $worker) use ($project_name) {
            $worker->exec('/usr/local/bin/php', ["/app/{$project_name}/artisan", "migrate", "--force"]);
        }, 'db migrate failed!', 'db migrate finished!'));
    }
}