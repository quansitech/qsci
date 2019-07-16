<?php
namespace QSCI;

use Swoole\Server;

class Pool
{
        public static $taskManagers = [];

        public static function addTaskManager(TaskManager $taskManager)
        {
            self::$taskManagers[$taskManager->getFd()] = $taskManager;
        }

        public static function getTaskManagerByPid($pid)
        {
            foreach(self::$taskManagers as $manager){
                if(array_key_exists($pid, $manager->getWorkingTasks())){
                    return $manager;
                }
            }
            return null;
        }

        public static function getTaskByPipe($pipe)
        {
            foreach(self::$taskManagers as $manager){
                foreach($manager->getWorkingTasks() as $task){
                    if($task->pipe == $pipe){
                        return $task;
                    }
                }
            }
            return null;
        }

        public static function clear(Server $serv){
            $managers = self::$taskManagers;
            self::$taskManagers = [];

            foreach($managers as $manager){
                $workingTasks = $manager->getWorkingTasks();
                array_walk($workingTasks, function($val, $key){
                    exec('/bin/sh ' . __DIR__ . '/killtree.sh ' . $key);
                });

                if($serv->exist($manager->getFd())){
                    $serv->close($manager->getFd());
                }
            }
        }
}
