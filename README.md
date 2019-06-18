# 基于云效和swoole构建的轻量级持续集成方案
![lincense](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)
![Pull request welcome](https://img.shields.io/badge/pr-welcome-green.svg?style=flat-square)

### 相关概念
+ ##### 持续集成
    是一种互联网开发快速迭代流程，频繁地将最新代码合并到主干，降低合并代码合并主干的难度，同时又更加容易的发现和修复问题。
    和持续集成相关的还有持续交付、持续部署。
+ ##### 云效
    云效是阿里云提供的一站式企业协同研发平台，其提供了需求、迭代、缺陷、文档、流水线等跟项目开发有关的一系列管理工具，目前处于免费使用阶段。
+ ##### swoole
    swoole是一个为php提供网络通信和异步io的扩展，目前也支持协程开发。本身提供强大的http服务功能，采用swoole作为微服务开发非常有优势。
    
### 为什么
+ ##### 为什么是云效 
   了解过持续集成的同学可能还知道jenkins、drone，这些都是目前最流行的持续集成工具。作者也做了大量的学习研究对比。那么为什么最后选择了云效呢？
   作者的公司是以小型外包项目为主的公司，讲求可管理高效低成本。而云效除流水线任务功能外还有强大的项目管理功能，对没有特殊需求的公司是完全免费的。
   同时对于不太复杂的项目，测试环境的构建流程也不是特别的需求，只需要用到自定义脚本能触发一系列的自定义任务即可。如代码更新、自动化测试。还可以
   大大降低流水线的运行时间。因此作者综合评估后觉得云效更适合。
+ ##### 为什么是swoole
   swoole自带web服务功能，这对于搭配docker快速构建运行环境非常方便，只需安装php及swoole扩展即可，swoole在构建微服务方面有天然优势。
   此外，swoole自带的Process在实现与子进程的IO输出交互时比使用shell_exec等函数要方便许多。可实现脚本运行时，可马上将任务执行的每个情况
   立即显示到流水线的执行日志。
   
### 效果图
<img src="https://github.com/tiderjian/qsci/blob/master/blob/ex.png" />

### 安装
```php
git clone https://github.com/tiderjian/qsci.git
```

### 启动server.php前的一些设置
+ ##### TOKEN常量
    验证请求的合法性
+ ##### PORT常量
    web服务监听的端口号
+ ##### ERROR_PREFIX与ERROR_SUFFIX
    云效用于定位任务是否执行出错的字符，如没有特别需求，使用默认的即可
+ ##### swoole webserver的配置
    同一时间一个项目只能执行一个流水线任务，如果有新的任务触发，旧任务会立即结束，因此采用单进程模式处理任务，切勿修改。（要处理多个项目的流水线，
    可为每一个项目启动一个docker容器）
    
 ### 运行swoole服务
 强烈建议将服务运行于docker容器中，1、方便安装。 2、当要处理多个项目的流水线时，可分别创建对应的docker，隔离运行环境。
 ```php
 php server.php
 ```
   
### 流程图
 <img src="https://user-images.githubusercontent.com/1665649/59674504-fbdb9e80-91f5-11e9-8526-ffa535d334e2.png" />
    
### 创建任务
+ ##### 创建任务文件夹
    在TaskProviders文件夹下新增任务名+Provider的文件夹，如任务名为example，文件夹为ExampleProvider。之后创建的所有脚本文件，任务类都将放于该文件夹下。
    
+ ##### 创建任务类
    以创建Example任务为例
    ```php
    <?php
    namespace QSCI\TaskProviders\ExampleProvider;
    
    use QSCI\TaskManager;
    use QSCI\TaskProviders\Provider;
    use Swoole\Http\Request;
    use Swoole\Process;
    
     /**
     * 必须继承Provider接口，并实现sign和init方法
     */
    class Example implements Provider{
    
        //与请求的action参数对应，如该sign返回字符串与action一致，则会触发该任务运行
        public function sign() : string
        {
            return 'example';
        }
    
        //具体的任务执行内容
        public function init(Request $request, TaskManager $manager)
        {
              .
              .
              .
        }
    }
    ```
+ ##### 实现具体的任务执行逻辑
    在任务类的init方法中编写具体的任务执行代码，以创建一个拉取最新代码，并删除缓存的任务为例
    ```php
    //获取请求参数
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

    //向任务管理器添加一条具体的任务
    //new Task对象
   //Task对象第一个参数为任务执行的闭包函数，闭包函数第一个参数返回一个swoole的Process对象。
   //Task对象第二个参数为任务出现异常返回的错误提示。
   //Task对象第三个参数为任务正常结束返回的成功提示。
   //Process是swoole的进程管理器，我们这里用到它的exec方法，和php的shell_exec方法功能一样，可执行一个系统命令，它有良好的输出IO处理
   //exec方法第一个参数为要调用的系统命令绝对路径，后面的数组是各个参数的集合，exec(命令，[参数1，参数2，参数3, ....]) =  命令 参数1 参数2 参数3....
   //Process的详细用法查阅swoole文档
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
    ```
    
 ### 配置云效
 云效的流水线配置方法请自行阅读阿里云的学习文档，这里只给出云效的自定义脚本代码
 ```blade
curl -d "post参数" "http://url地址?token=你设置的token&action=example" | tee test.log
st=`grep -c "ERROR\*\*\*\*\*.*\*\*\*\*\*ERROR" test.log`
if [ $st -gt 0 ]; then
    exit 1
fi
```
    
    ### lincense
    [MIT License](https://github.com/tiderjian/qsci/blob/master/LICENSE.MIT) AND [996ICU License](https://github.com/tiderjian/qsci/blob/master/LICENSE.996ICU)