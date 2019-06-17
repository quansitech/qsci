<?php
namespace QSCI;

use Swoole\Http\Response;
use Swoole\Process;

class TaskManager
{
    protected $tasks = [];
    protected $workingTasks = [];
    protected $response;
    protected $fd;

    public function __construct(Response $response)
    {
        $this->fd = $response->fd;
        self::setResponse($response);
    }

    protected function registerSignal(){
        Process::signal(SIGCHLD, function(){

            $status = Process::wait();
            $taskManager = Pool::getTaskManagerByPid($status['pid']);
            if(!$taskManager){
                return false;
            }
            $response = $taskManager->getResponse();
            $workingTask = $taskManager->getWorkingTaskByPid($status['pid']);
            $taskManager->clearWorking($workingTask);

            if($status['code'] != 0){
                $failureMsg = $workingTask->getFailureMsg();
                $response->write(ERROR_PREFIX . $failureMsg . ERROR_SUFFIX . PHP_EOL);
                $response->end();
            }
            else{
                if($successMsg = $workingTask->getSuccessMsg()){
                    $response->write($successMsg);
                }
                $response->write(PHP_EOL);
                $taskManager->asynRun();
            }
        });
    }

    public function getFd(){
        return $this->fd;
    }

    public function setWorking(Task $task)
    {
        $this->workingTasks[$task->pid] = $task;
    }

    public function clearWorking(Task $task)
    {
        $pid = $task->pid;
        if($this->workingTasks[$pid]){
            unset($this->workingTasks[$pid]);
        }
    }

    public function getWorkingTasks(){
        return $this->workingTasks;
    }

    public function getWorkingTaskByPid($pid){
        return $this->workingTasks[$pid];
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse() : Response
    {
        return $this->response;
    }

    public function addTask(Task $task)
    {
        return array_push($this->tasks, $task);
    }

    protected function getTask()
    {
        if(self::taskLength() == 0){
            $this->getResponse()->end();
            return false;
        }

        return array_shift($this->tasks);
    }

    protected function taskLength()
    {
        return count($this->tasks);
    }

    public function asynRun()
    {
        if(!($task = self::getTask())){
            return false;
        }

        $task->start();
        self::setWorking($task);

        self::registerSignal();

        swoole_event_add($task->pipe, function($pipe){
            $task = Pool::getTaskByPipe($pipe);
            if(!$task){
                return false;
            }
            $taskManager = Pool::getTaskManagerByPid($task->pid);
            $taskManager->getResponse()->write($task->read());
        });
    }
}