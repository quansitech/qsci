<?php
namespace QSCI;

use Swoole\Process;

class Task extends Process
{
    protected $failureMsg;
    protected $successMsg;

    /**
     * Task constructor.
     * @param callable 设置具体需执行的闭包函数
     * @param string 任务执行失败返回的提示信息
     * @param string 成功返回的提示信息
     */
    public function __construct(callable $callback, string $failureMsg = '', string $successMsg = '')
    {
        parent::__construct($callback, true);
        $this->setFailureMsg($failureMsg);
        $this->setSuccessMsg($successMsg);
    }

    public function setFailureMsg(string $msg)
    {
        $this->failureMsg = $msg;
    }

    public function getFailureMsg() : string
    {
        return $this->failureMsg;
    }

    public function setSuccessMsg(string $msg)
    {
        $this->successMsg = $msg;
    }

    public function getSuccessMsg() : string
    {
        return $this->successMsg;
    }
}