<?php

/**
 * 显示脚本用法并退出。
 */
function showUsageAndExit(): void
{
    // basename(__FILE__) 会自动获取当前脚本的文件名
    $scriptName = basename(__FILE__);
    echo "\n必需参数:\n";
    echo "  -s  要搜索的字符串。如果包含空格，请使用引号括起来。\n\n";
    echo "可选参数:\n";
    echo "  -d  要搜索的目录。(默认: 当前终端所在的目录)\n";
    echo "  -e  要搜索的文件扩展名，多个用逗号分隔。(默认: php)\n\n";
    echo "\n示例:\n";
    echo "\e[32m  php {$scriptName} -s \"class User\" -d ./app -e php,phtml\e[0m\n\n";
    exit;
}

// 1. 只解析 -s, -d, -e 三个参数
$options = getopt("s:d:e:");

// 2. 检查必需参数 -s 是否存在
if (!isset($options['s']) || empty($options['s'])) {
    echo "\n\e[31m错误: 必须提供 -s <搜索字符串> 参数。\e[0m\n\n";
    showUsageAndExit();
}
$searchString = $options['s'];


// 3. 处理目录参数 -d (这是关键的修复)
//   - 默认使用 getcwd() 获取当前终端的工作目录
//   - 使用 realpath() 将用户输入的路径转换为绝对路径并验证其存在性
$userInputDir = $options['d'] ?? getcwd();
$directory = realpath($userInputDir);

if ($directory === false) {
    echo "\n\e[31m错误: 目录 '{$userInputDir}' 不存在。\e[0m\n";
    exit(1);
}
if (!is_dir($directory) || !is_readable($directory)) {
    echo "\n\e[31m错误: 目录 '{$directory}' 不可读，请检查路径是否正确以及是否有读取权限。\e[0m\n";
    exit(1);
}

// 4. 处理文件扩展名参数 -e
$extensionsStr = $options['e'] ?? 'php';
$allowedExtensions = explode(',', str_replace(' ', '', $extensionsStr));

// --- 开始执行搜索 ---
echo "\n=================================================================\n";
echo "开始搜索: '{$searchString}'\n";
echo "搜索目录: '{$directory}'\n";
echo "文件类型: '" . implode(', ', $allowedExtensions) . "'\n";
echo "=================================================================\n\n";

$matchFound = false;

try {
    $directoryIterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directoryIterator);

    foreach ($iterator as $file) {
        // strtolower确保扩展名比较不区分大小写 (例如 .PHP 和 .php)
        if (in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            $filePath = $file->getRealPath();
            $fileHandle = @fopen($filePath, 'r');

            if ($fileHandle) {
                $lineNumber = 1;
                while (($line = fgets($fileHandle)) !== false) {
                    if (strpos($line, $searchString) !== false) {
                        $matchFound = true;
                        echo "找到匹配项:\n";
                        echo "  -> 文件: {$filePath}\n";
                        echo "  -> 行号: {$lineNumber}\n";
                        echo "  -> 内容: " . rtrim($line) . "\n\n";
                    }
                    $lineNumber++;
                }
                fclose($fileHandle);
            }
        }
    }
} catch (Exception $e) {
    echo "\e[31m发生错误: " . $e->getMessage() . "\e[0m\n";
    exit(1);
}


if (!$matchFound) {
    echo "未找到任何匹配项。\n";
}

echo "搜索完成。\n";

?>