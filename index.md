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
数据采集：composer require jaeger/querylist
图Base64：composer require melihovv/base64-image-decoder

文件添加ZIP格式并下载：composer require maennchen/zipstream-php
文字转拼音：composer require overtrue/pinyin

Thinkphp扩展插件包大全：
https://sites.thinkphp.cn/1556332
```

## git版本回退
```
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
    index  	index.html index.htm index.php; 

    ssl_certificate /data/kdgood/ssl/$ssl_server_name.crt;
    ssl_certificate_key /data/kdgood/ssl/$ssl_server_name.key;
    ssl_session_cache shared:SSL:20m;
    ssl_session_timeout  5m;
    #ssl_protocols  SSLv2 SSLv3 TLSv1;
    ssl_protocols  TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers  HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers   on;

    location / {
        if (!-e $request_filename) {
            rewrite  ^(.*)$  /index.php?s=$1  last;
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
