<?php
namespace QSCI\TaskProviders\ExampleProvider;

use Exception;
use QSCI\Task;
use QSCI\TaskManager;
use QSCI\TaskProviders\Provider;
use Swoole\Http\Request;
use Swoole\Process;

class Example implements Provider{

    //与请求的action参数对应，如该sign返回字符串与action一致，则会触发该任务运行
    public function sign() : string
    {
        return 'example';
    }

    //任务设置
    public function init(Request $request, TaskManager $manager)
    {
        $project_name = $request->post['project'];

        //参数安全过滤
        if(!preg_match("/^[A-Za-z0-9_\/\-]+$/", $project_name)){
            throw new Exception('invalid project name');
        }

        $branch = $request->post['branch'];
        //参数安全过滤
        if (!preg_match("/^[A-Za-z0-9_\-@]+$/", $branch)) {
            throw new Exception('invalid branch');
        }

        //设置拉取代码任务
        $manager->addTask(new Task(function (Process $worker) use ($project_name, $branch) {
            $worker->exec('/bin/sh', [__DIR__ . '/gitPull.sh', "/app/{$project_name}", $branch]);
        }, 'git pull error!', 'git pull finished!'));

        //设置清空缓存任务
        $manager->addTask(new Task(function (Process $worker) use ($project_name) {
            $dir = "/app/{$project_name}/app/Runtime";
            if (file_exists($dir)) {
                $worker->exec('/bin/rm', ["-rf", "{$dir}/*"]);
            }
        }, 'clear runtime failed!', 'clear runtime finished!'));
    }
}