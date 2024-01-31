<?php
// +--------------------------------------------------------------------------------------------------------------
// | dirSync文件，用于自动获取git下变更的文件，并在两个目录间同步
// +--------------------------------------------------------------------------------------------------------------

$target = 'E:/hro/ceshibox/';

// 校验目录是否存在，不存在则创建
if(!is_dir($target))
{
    mkdir($target, 0777, true);
}

// 提示用户输入
echo '文件同步的方式，请输入数字：' . PHP_EOL;
echo '1：未提交同步' . PHP_EOL;
echo '2：已提交同步' . PHP_EOL;
echo '3：指定commitID同步' . PHP_EOL;
echo '4：测试目录流程' . PHP_EOL;
echo '   41：测试目录分支创建' . PHP_EOL;
echo '   42：测试目录分支发布' . PHP_EOL;
echo '5：查看当前分支最后一次的修改' . PHP_EOL;
echo '6：切换到指定分支' . PHP_EOL;

// 获取用户输入
$handle = fgets(STDIN);
$handle = trim($handle);

// 打印用户输入
// print_r($handle);



// +--------------------------------------------------------------------------------------------------------------
// | 未提交同步
// +--------------------------------------------------------------------------------------------------------------

if(1 == $handle) 
{
    $addedFiles = [];
    $modifiedFiles = [];
    $deletedFiles = [];

    // 获取新增（A）、修改（M）的文件
    exec('git ls-files --others --exclude-standard', $addedFiles);
    exec('git diff --diff-filter=M --name-only', $modifiedFiles);

    // 去除输出中的空行
    $addedFiles = array_filter($addedFiles, 'strlen');
    $modifiedFiles = array_filter($modifiedFiles, 'strlen');

    // 打印列表
    // print_r($addedFiles);
    // print_r($modifiedFiles);
    // exit;

    echo PHP_EOL;
    echo "同步列表：" . PHP_EOL;

    // 同步新增和修改的文件
    foreach (array_merge($addedFiles, $modifiedFiles) as $file) 
    {
        // 获取文件的目录
        $fileDir = dirname($file);

        // 如果目录不存在，则创建目录
        if (!is_dir($target . $fileDir)) 
        {
            mkdir($target . $fileDir, 0777, true);
        }

        // 获取文件的新路径
        $newFile = $target . $file;

        // 复制文件到目标目录
        if(copy($file, $newFile))
        {
            echo "\033[32;5;1mOK：{$file}  ->  {$newFile}\033[0m" . PHP_EOL;
        }
        else
        {
            echo "\033[38;5;1mERROR：{$file}  ->  {$newFile}\033[0m" . PHP_EOL;
        }
    }

    echo PHP_EOL;
    exit;
}



// +--------------------------------------------------------------------------------------------------------------
// | 已提交同步
// +--------------------------------------------------------------------------------------------------------------

if(2 == $handle || 3 == $handle)
{
    // 判断是否自动获取commitID
    if(3 == $handle)
    {
        // 提示用户输入
        echo '请输入commitID：' . PHP_EOL;

        // 获取用户输入
        $commitID = fgets(STDIN);
        $commitID = trim($commitID);

        // 打印用户输入
        // print_r($commitID);
    }
    else
    {
        // 获取当前分支最后一次提交的commitID
        $currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
        $commitID = trim(shell_exec("git rev-parse {$currentBranch}"));
    }

    // 获取commitID对应的文件列表
    $commitFiles = explode("\n", shell_exec("git show --name-only --pretty=format: $commitID"));
    $commitFiles = array_filter($commitFiles, 'strlen');

    echo PHP_EOL;
    echo "同步列表[{$currentBranch}]：" . PHP_EOL;

    foreach ($commitFiles as $file) 
    {
        // 判断文件是否存在
        if(!file_exists($file))
        {
            echo "\033[38;5;1mERROR：{$file}  ->  文件不存在\033[0m" . PHP_EOL;
        }

        // 获取文件的目录
        $fileDir = dirname($file);

        // 如果目录不存在，则创建目录
        if (!is_dir($target . $fileDir)) 
        {
            mkdir($target . $fileDir, 0777, true);
        }

        // 获取文件的新路径
        $newFile = $target . $file;

        // 复制文件到目标目录
        if(copy($file, $newFile))
        {
            echo "\033[32;5;1mOK：{$file}  ->  {$newFile}\033[0m" . PHP_EOL;
        }
        else
        {
            echo "\033[38;5;1mERROR：{$file}  ->  {$newFile}\033[0m" . PHP_EOL;
        }
    }

    echo PHP_EOL;
    exit;
}



// +--------------------------------------------------------------------------------------------------------------
// | 测试目录流程
// +--------------------------------------------------------------------------------------------------------------

if(41 == $handle)
{
    // 获取正式目录的当前分支名把prod-去掉，前面追加devkeep-日期当成分支名
    $currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
    $branchName = 'devkeep-' . date('md') . '-' . str_replace('prod-', '', $currentBranch);

    echo PHP_EOL;
    echo "测试目录分支创建流程：" . PHP_EOL . PHP_EOL;

    echo "第1步(切换到master)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git checkout master") . PHP_EOL;

    echo "第2步(拉取最新代码)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git pull") . PHP_EOL;

    echo "第3步(创建分支)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git checkout -b {$branchName}") . PHP_EOL;
    echo PHP_EOL;
    exit;
}

if(42 == $handle)
{
    // 获取测试目录的当前分支名
    $currentBranch = trim(shell_exec("cd {$target} && git rev-parse --abbrev-ref HEAD"));

    echo PHP_EOL;
    echo "测试目录发布流程：" . PHP_EOL . PHP_EOL;

    echo "第1步(git add)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git add .") . PHP_EOL;

    echo "第2步(git commit)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git commit -m '{$currentBranch}'") . PHP_EOL;

    echo "第3步(推送本地分支到远程)：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;
    echo shell_exec("cd {$target} && git push -u origin {$currentBranch}") . PHP_EOL;

    echo PHP_EOL;
    exit;
}


// +--------------------------------------------------------------------------------------------------------------
// | 查看当前分支最后一次的修改
// +--------------------------------------------------------------------------------------------------------------

if(5 == $handle)
{
    // 获取当前分支最后一次提交的commitID
    $currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
    $commitID = trim(shell_exec("git rev-parse {$currentBranch}"));

    // 获取commitID对应的文件列表
    $commitFiles = explode("\n", shell_exec("git show --name-only --pretty=format: $commitID"));
    $commitFiles = array_filter($commitFiles, 'strlen');

    echo PHP_EOL;
    echo "文件列表[{$currentBranch}]：" . PHP_EOL;

    foreach ($commitFiles as $file) 
    {
        echo "\033[32;5;1m{$file}\033[0m" . PHP_EOL;
    }

    echo PHP_EOL;
    exit;
}


// +--------------------------------------------------------------------------------------------------------------
// | 切换到指定分支
// +--------------------------------------------------------------------------------------------------------------

if(6 == $handle)
{
   // 执行Git命令，获取所有的分支
    $branches = shell_exec('git branch');
    $branches = explode("\n", trim($branches));

    // 为分支添加数字标记，并显示它们
    echo PHP_EOL;
    echo "所有分支列表：" . PHP_EOL;
    echo "----------------------------------------------------------------------------------------------" . PHP_EOL;

    foreach ($branches as $i => $branch) 
    {
        // 如果包含*号，则表示当前分支，不显示
        if (strpos($branch, '*') !== false) 
        {
            echo "\033[32;5;1m{$i}. {$branch}\033[0m" . PHP_EOL;
        }
        else
        {
            echo $i . ". " . $branch . PHP_EOL;
        }
    }

    // 获取用户输入
    $index = fgets(STDIN);
    $index = trim($index);

    // 检查输入的有效性
    if (!isset($branches[$index])) 
    {
        echo '输入的数字没有对应的分支' . PHP_EOL;
        exit;
    }

    // 切换到指定的分支
    $branch = trim($branches[$index]);
    echo shell_exec("git checkout {$branch}") . PHP_EOL;

    echo PHP_EOL;
    exit;
}