<?php

$directoryPath = 'd:\phpEnv\www\buybestbuy\app\Console';

$iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($directoryPath),
    \RecursiveIteratorIterator::SELF_FIRST
);

// 遍历所有文件
foreach ($iterator as $file) 
{
    try {
        if ($file->isFile()) 
        {
            $content = file_get_contents($file->getPathname());
            $tokens = token_get_all($content);
            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                    dd($this->extractDocComment($token[1]));
                }
            }
        } 
    }
    catch (\UnexpectedValueException $e) 
    {
        echo "Could not access file: " . $file->getPathname() . "\n";
    }
}