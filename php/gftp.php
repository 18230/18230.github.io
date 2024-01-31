<?php
// +----------------------------------------------------------------------
// | PHP-Git业务逻辑，获取git仓库的变更文件列表
// +----------------------------------------------------------------------
set_time_limit(0);

// 远程目录 (本地文件夹相对于ftp根目录的路径，所有子目录须一一对应)
$remoteDir = '/';

// 提取是否有冲突的文件
echo PHP_EOL . '[正在执行git pull]: ' . PHP_EOL;
echo '-----------------------------' . PHP_EOL;
exec('git pull', $output);
$conflictFiles = [];
foreach ($output as $line) 
{
    echo $line . PHP_EOL;

    if (preg_match('/CONFLICT.* (.*)$/', $line, $matches)) 
    {
        $conflictFiles[] = $matches[1];
    }
}

echo PHP_EOL;

if(count($conflictFiles) > 0)
{
    foreach ($conflictFiles as $file)
    {
        echo "\033[38;5;1m冲突：\033[0m" . $file . PHP_EOL;
    }

    echo PHP_EOL;
    echo "\033[38;5;1m请先解决冲突后再执行上传操作\033[0m" . '' . PHP_EOL;
    exit;
}

// 获取参数
$params = $_SERVER['argv'];

if(2 === count($params) && in_array($params[1], ['all']))
{
    echo PHP_EOL . '[正在执行npm run build:test，打包中...]: ' . PHP_EOL;
    echo '------------------------------------------------------------------' . PHP_EOL;
    exec('cd view && npm run build:test', $content);
    print_r($content);
    echo PHP_EOL . PHP_EOL;

    if('build' === $params[1])
    {
        exit;
    }
}

// 初始化
$untrackedFiles = [];
$modifiedFiles = [];
$deletedFiles = [];

// 未添加的文件列表
exec('git ls-files --others --exclude-standard', $untrackedFiles);

// 修改过的文件列表
exec('git diff --diff-filter=M --name-only', $modifiedFiles);

// 删除的文件列表
exec('git diff --name-only --diff-filter=D', $deletedFiles);

// 打印列表
// print_r('### add ###');
// print_r($untrackedFiles);
// print_r("\n");
// print_r("\n");

// print_r('### update ###');
// print_r($modifiedFiles);
// print_r("\n");
// print_r("\n");

// print_r('### delete ###');
// print_r($deletedFiles);
// print_r("\n");
// print_r("\n");

// +----------------------------------------------------------------------
// | PHP-Ftp业务逻辑，执行文件上传操作
// +----------------------------------------------------------------------

// 连接ftp
$con = @ftp_connect('111.229.78.171', 21);

// 登录ftp
if(@ftp_login($con, 'testview', 'sDMwz4sGwfBrsnGz'))
{
    // default connection FTP_USEPASVADDRESS
    ftp_set_option($con, FTP_USEPASVADDRESS, false);

    // TIMEOUT 15s
    ftp_set_option($con, FTP_TIMEOUT_SEC, 15);

    // PASV MODE
    ftp_pasv($con, true);

    // 递归创建目录函数
    function createDirectory($con, $path) 
    {
        $parts = explode($remoteDir, $path);

        foreach ($parts as $part) 
        {
            if (!@ftp_chdir($con, $part)) 
            {
                @ftp_mkdir($con, $part);
                @ftp_chdir($con, $part);
            }
        }

        // 移动文件到根目录
        ftp_chdir($con, $remoteDir);
    }


    if(2 === count($params) && in_array($params[1], ['all']))
    {
        // 前端打包文件上传
        echo PHP_EOL . '[前端打包文件上传FTP]: ' . PHP_EOL;
        echo '------------------------------------------------------------------' . PHP_EOL;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('./view/dist/'),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) 
        {
            if (!$file->isDir()) 
            {
                $newFilePath = str_replace("dist", "public", $file->getPathname());
                $newFilePath = str_replace("\\", "/", $newFilePath);

                if(@ftp_put($con, $newFilePath, $file->getPathname(), FTP_BINARY))
                {
                    echo 'OK: ' . $file . PHP_EOL;
                }
                else
                {
                    echo "\033[38;5;1mERROR:\033[0m " . $file . PHP_EOL;
                }
            }
        }
        echo '-------------------------------------------------------------------' . PHP_EOL;
        echo PHP_EOL . PHP_EOL;

        if('view-ftp' === $params[1])
        {
            exit;
        }
    }

    
    echo PHP_EOL . '[Git仓库中变动的文件上传FTP]: ' . PHP_EOL;
    echo '------------------------------' . PHP_EOL;

    // 新添加的文件
    if(count($untrackedFiles) > 0)
    {
        foreach ($untrackedFiles as $file)
        {
            $dirname = dirname($file);
    
            // 目录不存在则创建
            createDirectory($con, $dirname);
    
            if(@ftp_put($con, $file, $file, FTP_BINARY))
            {
                echo "\033[32;5;1mOK：\033[0m+++ " . $file . PHP_EOL;
            }
            else
            {
                echo "\033[38;5;1mERROR:\033[0m " . $file . PHP_EOL;
            }
        }
    }

    // 更新的文件
    if(count($modifiedFiles) > 0)
    {
        foreach ($modifiedFiles as $file)
        {
            $dirname = dirname($file);
    
            // 目录不存在则创建
            createDirectory($con, $dirname);
    
            if(@ftp_put($con, $file, $file, FTP_BINARY))
            {
                echo "\033[32;5;1mOK：\033[0m" . $file . PHP_EOL;
            }
            else
            {
                echo "\033[38;5;1mERROR：\033[0m" . $file . PHP_EOL;
            }
        }
    }

    if(empty($untrackedFiles) && empty($modifiedFiles))
    {
        echo 'No file to upload' . PHP_EOL;
    }
}
else
{
    echo 'FTP LOGIN ERROR';
    echo PHP_EOL;
}
