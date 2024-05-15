## devkeep的笔记
```
永久邮箱：
wanggaoqi888@gmail.com
devkeep@163.com

临时邮箱：
363927173@qq.com
devkeep@skeep.cc
```

## composer
```
composer 手动加载语法：
"autoload":{
    "psr-4": {
          "Workerman\\":"vendor/workerman/workerman",
    	    "GatewayWorker\\": "vendor/workerman/gateway-worker/src"
    	    "命名空间\\": "目录地址"
    }
}
composer dump-autoload

composer下载地址和腾讯云镜像：
https://getcomposer.org/download/
composer config -g repos.packagist composer https://mirrors.cloud.tencent.com/composer/

composer更新到指定版本：
composer self-update <version>

composer常用包：
常用库大全：https://github.com/JingwenTian/awesome-php
文档生成：composer require hg/apidoc
常用函数：composer require devkeep/tools
微信支付：composer require zoujingli/wechat-developer
数 据 库：composer require catfan/medoo
数 据 库：composer require topthink/think-orm
邮件发送：composer require phpmailer/phpmailer
二 维 码：composer require endroid/qr-code
数据采集：composer require symfony/dom-crawler
图Base64：composer require melihovv/base64-image-decoder

文件添加ZIP格式并下载：composer require maennchen/zipstream-php
文字转拼音：composer require overtrue/pinyin

Thinkphp扩展插件包大全：
https://sites.thinkphp.cn/1556332

PHP-FPM下静默执行：
if(function_exists('fastcgi_finish_request')) 
{
    set_time_limit(0);
    ignore_user_abort(true);
    fastcgi_finish_request();
}


PHP和常用扩展包下载：
windows官方PHP团队：https://windows.php.net/team
第三方PHP扩展包下载：https://phpext.phptools.online
PHP下载地址：https://windows.php.net/downloads/releases/archives
PHP扩展包下载地址：https://windows.php.net/downloads/pecl/releases
php redis扩展包下载地址：https://phpext.phptools.online/extension/database/redis-99
PHP代码检测工具：：https://phptools.online
```

## Linux杀掉指定端口上的程序
```

#!/bin/bash

# 定义要杀掉的端口号
port=8888

# 查找占用指定端口的程序的PID
pid=$(lsof -t -i :$port)

# 杀掉对应的进程
if [[ -n $pid ]]; then
  kill -9 $pid
else
  echo "No process found on port $port"
fi
```


## Nginx反代远程地址
```
server {
    listen 80;
    server_name chat.xxx.com; 
    location / {
        proxy_pass https://chat.openai.com; 
        proxy_set_header Host chat.openai.com; 
        proxy_buffering off;
        proxy_ssl_server_name on;
    }
}
```


## Docker常用
```
安装docker及docker compose
curl -fsSL https://get.docker.com -o get-docker.sh && sh ./get-docker.sh

检查docker是否安装成功
docker -v
docker compose version

下载docker-compose.yaml
wget https://www.888.com/m1k1o/neko/master/docker-compose.yaml

启动docker程序（docker-compose.yml所在目录）
sudo docker compose up -d

重启（docker-compose.yml所在目录）
docker-compose restart

停止Docker Compose中指定的服务
docker-compose stop [服务名称]
```

## git常用操作命令
```
查看仓库地址
git remote -v

暂存不提交和撤消最近一次操作
git stash
git stash apply

未提交
1.已经在工作区修改文件，但还未执行 git add 提交到暂存区。
2.已经执行了 git add，但还未执行 git commit 提交本地仓库。
回退：git reset --hard

已提交未推送
回退：git reset --hard HEAD^
多版本回退：git reset --hard HEAD~N （N：要回退的次数）

指定版本回退：
git reset --hard <commit_id>

直接回退到远程最新版本：
git reset --hard origin/master

已推送回退(示例)：
git reset --hard HEAD^
git push -f

对比上个版本的所有差异
git diff HEAD^

对比指定文件上个版本的差异
git diff HEAD^ -- <path>

对比指定文件的两个版本之间的差异
git diff <提交ID1> <提交ID2> -- <文件路径>

git的常用windows批处理
------------------------------------------------
@echo off
chcp 65001
SET /P branchName="请输入要提交的分支名称: "
SET /P commitMessage="请输入commit提交信息: "

REM 添加所有更改
git add .

REM 提交更改
git commit -m "%commitMessage%"

REM 可选：将分支推送到远程仓库
git push origin %branchName%

echo 当前分支操作完毕。继续下一个分支操作：

REM 切换到主分支
git checkout master

REM 输入下一个分支名
SET /P nextBranchName="请输入下一个功能的分支名称: "

REM 切换到下一个分支
git checkout -b %nextBranchName%
--------------------------------------------------


查看代理：
git config --global --get http.proxy
git config --global --get https.proxy

设置代理：
git config --global http.proxy socks5://127.0.0.1:7890
git config --global https.proxy socks5://127.0.0.1:7890

取消代理：
git config --global --unset http.proxy
git config --global --unset https.proxy
```

## git钩子
```
sudo vim post-receive

#!/bin/sh
cd /www/wwwroot
unset GIT_DIR
eval ssh-agent
ssh-add /home/git/.ssh/id_rsa
git pull origin master
```

## svn钩子
```
sudo vim hooks/post-commit

#!/bin/sh
export LANG=en_US.UTF-8
svn update --username hanghang --password 123456 /data/project --no-auth-cache
```

## git pull 提示：no tty present and no askpass program specified
```
sudo vi /etc/sudoers 
在sudoers文件中加一行（www为所属用户，免密登录）：
www ALL=(ALL:ALL) NOPASSWD: ALL
```

## nginx配置（包含动态ssl）
```
server {
    listen 80;
    server_name ~^(?<profix>\w+)\.skeep\.cc$;
    return 301 https://$host$request_uri;
}

server {
    listen  443 ssl;
    server_name ~^(?<profix>\w+)\.skeep\.cc$;
    root    /data/kdgood/$profix/public;

    ssl_certificate /data/kdgood/ssl/$ssl_server_name.crt;
    ssl_certificate_key /data/kdgood/ssl/$ssl_server_name.key;
    ssl_session_cache shared:SSL:20m;
    ssl_session_timeout  5m;
    #ssl_protocols  SSLv2 SSLv3 TLSv1;
    ssl_protocols  TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers  HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers   on;

    location / {
        index  	index.html index.htm index.php; 
        
        if (!-e $request_filename) {
            rewrite  ^(.*)$  /index.php?s=/$1  last;
            break;
        }
    }

    location /static {
        location ~ \.php$ {
            deny all;
        }
    }

    location ~ \.php(.*)$ {
        fastcgi_pass  127.0.0.1:9000;
        fastcgi_split_path_info  ^(.+\.php)(.*)$;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO $fastcgi_path_info;
        include        fastcgi_params;
    }
}
```

## 公私钥
```
1.生成公私钥 ssh-keygen -t rsa（windows: C:\Users\Administrator\.ssh）
2.添加私钥到git（linux）： 
ssh-add ~/.ssh/id_rsa
ssh-agent bash

sudo vim /etc/sudoers （增加nginx或者fpm可以免密执行登录）
添加： www-data  ALL=(ALL:ALL) NOPASSWD: ALL
```

## git web钩子调用
```
<?php


    // --------------- pull.sh -----------------------
    // #!/bin/sh
    // cd /data/yunxiaoyi
    // eval `ssh-agent`
    // ssh-add /home/ubuntu/.ssh/id_rsa
    // git pull origin master
    // --------------- pull.sh -----------------------
    

    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        exec("sudo /data/pull.sh 2>&1", $out, $return);
        print_r(implode("\n", $out));
        exit();
    }

?>

<!doctype html>
<html>
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>代码同步</title>
        <style>
            body
            {
                margin: 0;
                padding: 0;
            }   

            .syc
            {
                width: 100px;
                height: 32px;
                line-height: 32px;
            }

            .c
            {
                margin: 20px 0 0 30px;
            }
        </style>
    </head>

    <body>
        <button class="syc c">代码同步</button>
        <div class="box c"></div>

        <script src="https://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
        <script>

            $('.syc').on('click', function(){

                $.ajax({
                    type: 'post',
                    url: './syc.php',
                    data: {},
                    beforeSend: function()
                    {
                        $('.box').html('正在同步，请稍后...');
                    },
                    success: function (data) 
                    {
                        console.log(data);
                        $('.box').html(data.replace(/\n/g,"<br/>"));
                    },
                    error: function(error)
                    {
                        console.log(error);
                    }
                });
            })

        </script>
    </body>
</html>

```

## 开源实现的web视频播放器
```
https://github.com/goldvideo/h265player
```

## windows从linux下载文件
```
scp root@0.0.0.0:/data/linux/a.txt /path/on/windows/b.txt
```

## windows批处理命令执行git操作
```
%git提交%
git pull origin master
git add .
git commit -m "auto"
git push origin master
%同步远程目录代码%
curl -G https://miniapp.funengyun.top/syc.php?c=1
pause
```

## Windows11 查看已知Wifi帐户列表和密码
```
在cmd下执行命令：
1.netsh wlan show profiles
2.netsh wlan show profiles WIFI名称 key=clear
```

## 打开windows自启动目录：
```
在cmd下执行命令：
start shell:startup
```

## win11调用IE内核浏览
```
CreateObject("InternetExplorer.Application").Visible=true
保存为123.vbs
```

### windows实用工具
```
卸载软件：https://geekuninstaller.com/download
解压软件：https://www.7-zip.org/download.html
杀毒软件：https://www.huorong.cn/
文件搜索：https://www.voidtools.com/zh-cn/
U启动盘 ：https://github.com/pbatard/rufus/releases
增强命令：https://cmder.app
文件对比：https://winmerge.org/
```

### windows下 “npm run dev” 静默运行
```
npm run dev > output.log 2>&1 &
```

### win11下重启wsl
```
wsl --shutdown
```

### electron-builder打包慢或打包失败的解决办法
```
npm config edit

electron_builder_binaries_mirror=http://npm.taobao.org/mirrors/electron-builder-binaries/
electron_mirror=https://npm.taobao.org/mirrors/electron/
registry=https://registry.npm.taobao.org/
```

### npm install安装时报错 canvas node-pre-gyp install --fallback-to-build --update-binary
```
单独安装
npm install canvas --canvas_binary_host_mirror=https://registry.npmmirror.com/-/binary/canvas
```
