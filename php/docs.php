<?php

$directoryPath = 'd:\phpEnv\www\buybestbuy\app\Console';

$iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($directoryPath),
    \RecursiveIteratorIterator::SELF_FIRST
);

/**
 * Extracts the documentation comment from a PHP token.
 */
function extractDocComment($comment) 
{
    // Remove leading and trailing comments
    $comment = trim(preg_replace('/^\/\*\*|\*\/$/', '', $comment));
    $lines = preg_split('/\r\n|\r|\n/', $comment);

    // Remove leading asterisks
    $lines = array_map(function($line) {
        return trim(preg_replace('/^\s*\*\s?/', '', $line));
    }, $lines);

    // Remove empty lines
    $lines = array_filter($lines, function($line) {
        return !empty($line);
    });

    return $lines;
}

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

