<?php
namespace QSCI\TaskProviders;

use QSCI\TaskManager;
use Swoole\Http\Request;

interface Provider{

    public function init(Request $request, TaskManager $manager);

    public function sign():string;
}