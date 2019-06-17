# 基于云效和swoole构建的轻量级持续集成方案

### 相关概念
+ #### 持续集成
    是一种互联网开发快速迭代流程，频繁地将最新代码合并到主干，降低合并代码合并主干的难度，同时又更加容易的发现和修复问题。
    和持续集成相关的还有持续交付、持续部署。
+ #### 云效
    云效是阿里云提供的一站式企业协同研发平台，其提供了需求、迭代、缺陷、文档、流水线等跟项目开发有关的一系列管理工具，目前处于免费使用阶段。
+ #### swoole
    swoole是一个为php提供网络通信和异步io的扩展，目前也支持协程开发。本身提供强大的http服务功能，采用swoole作为微服务开发非常有优势。
    
### 安装
```php
git clone https://github.com/tiderjian/qsci.git
```

### 设置