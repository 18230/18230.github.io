<?php

use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Exception;

/**
 * 格式化输出数据
 *
 * @param mixed $var 要格式化输出的数据
 * @return void
 */
function v($var)
{
    if(is_array($var))
    {
        ksort($var);
    }
    
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    exit;
}

/**
 * 消息推送
 * 
 * @param string $msg 消息
 * @param string $event 事件类型
 * @param string $channel 推送频道
 * @return void
 */
function pusher($msg, $event = 'toast', $channel = 'hro')
{
    $options = [
        'cluster' => 'ap1',
        // 'useTLS' => true
    ];
        
    $pusher = new \Pusher\Pusher('ed676b670dc7708a51d4', '681dbc42eac9a6a7baed', '1613552', $options);
    $data['msg'] = $msg;
    $pusher->trigger($channel, $event, $data);
}

/**
 * 生成Excel模板并立即下载 (此代码由ChatGPT-4构建生成)
 *
 * @param array $columns Excel表格的列数组
 * @param string $filename 输出的文件名
 * @return void
 */
function createExcelTemplate(array $columns, string $filename = 'example.xlsx')
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Sheet1');

    // 数据列
    $columnLetters = range('A', 'Z');

    foreach ($columns as $index => $column) {
        $colLetter = $columnLetters[$index];

        // 设置表标题
        $sheet->setCellValue($colLetter . '1', $column['title']);

        // 设置第一行的样式
        $sheet->getStyle($colLetter . '1')
            ->getFont()
            ->setBold(true);
        $sheet->getStyle($colLetter . '1')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // 设置宽度
        if (isset($column['width'])) {
            $sheet->getColumnDimension($colLetter)
                ->setWidth($column['width']);
        } else {
            $sheet->getColumnDimension($colLetter)
                ->setAutoSize(true);
        }

        // 判断是否有下拉选项
        if (isset($column['options']) && is_array($column['options'])) {
            // 将下拉选项添加到远列并隐藏
            foreach ($column['options'] as $index => $option) {
                // 设置远列的单元格值并格式转为文本，防止下拉选项被Excel自动转换为数字
                $sheet->setCellValueExplicit('AA' . $colLetter . ($index + 1), $option, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                // 隐藏远列
                $sheet->getColumnDimension('AA' . $colLetter)->setVisible(false);
            }

            // 在主工作表的单元格中引用远列的选项
            $dataValidation = new DataValidation();
            $dataValidation->setType(DataValidation::TYPE_LIST);
            $dataValidation->setShowDropDown(true);
            $dataValidation->setFormula1('Sheet1!$AA' . $colLetter . '$1:$AA' . $colLetter . count($column['options']));

            // 应用200列的数据验证
            for ($i = 2; $i <= 200; $i++) {
                $sheet->setDataValidation($colLetter . $i, $dataValidation);
            }
        }
    }

    // 设置HTTP头部信息以强制浏览器下载文件
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new WriterXlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


/**
 * 读取Excel文件 (此代码由ChatGPT-4构建生成)
 *
 * @param string $filePath 要读取的文件路径
 * @param int $maxColumnCount 限定读取的最大列数
 * @return array
 */
function readExcelFile(string $filePath, int $maxColumnCount = 0)
{
    // 确保文件存在
    if (!file_exists($filePath)) {
        throw new Exception('文件不存在');
    }

    // 初始化Reader对象，以流式读取节省内存
    $reader = new ReaderXlsx();
    $reader->setReadDataOnly(true);

    try {
        // 加载文件
        $spreadsheet = $reader->load($filePath);

        // 获取第一个工作表
        $worksheet = $spreadsheet->getActiveSheet();

        // 初始化数据数组和空行计数器
        $data = [];
        $emptyRowCount = 0;

        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            $isEmptyRow = true;

            // 设置迭代器只读取有数据的列
            $cellIterator = $row->getCellIterator(
                'A',
                $maxColumnCount ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxColumnCount) : null
            );
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();
                $rowData[] = $cellValue;

                if ($cellValue != null && $cellValue !== '') {
                    $isEmptyRow = false;
                }
            }

            // 空行数据累加
            if ($isEmptyRow) {
                $emptyRowCount++;
            } else {
                $data[] = $rowData;

                // 如果当前行不为空，则重置空行计数器
                $emptyRowCount = 0;
            }

            // 连续5行为空行，则停止读取，说明数据已读取完成
            if ($emptyRowCount >= 5) {
                break;
            }
        }

        // 销毁对象，释放内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $data;
    } catch (Exception $e) {
        throw new Exception('处理失败: ' . $e->getMessage());
    }
}

/**
 * 导出Excel文件并立即下载 (此代码由ChatGPT-4构建生成)
 *
 * @param array $headerArr Excel表格的标题数组
 * @param array $dataArr 数据数组
 * @param string $fileName 输出的文件名
 * @param bool $isSave 是否保存到服务器
 * @param string $dirPath 保存到服务器的路径
 * @return void
 */
function exportToExcel(array $headerArr, array $dataArr, string $fileName = 'export.xlsx', bool $isSave = false, string $dirPath = '')
{
    if (class_exists('\Cache\Adapter\Redis\RedisCachePool') && class_exists('\Cache\Bridge\SimpleCache\SimpleCacheBridge')) {
        // 设置导出时缓存
        $client = new \Redis();
        $client->connect('127.0.0.1', 6379);
        $pool = new \Cache\Adapter\Redis\RedisCachePool($client);
        $simpleCache = new \Cache\Bridge\SimpleCache\SimpleCacheBridge($pool);
        Settings::setCache($simpleCache);
    }

    // 初始化Spreadsheet对象
    $spreadsheet = new Spreadsheet();
    $activeSheet = $spreadsheet->getActiveSheet();

    // 设置标题
    $col = 'A';
    foreach ($headerArr as $header) {
        $activeSheet->setCellValue($col . '1', $header);
        $col++;
    }

    // 填充数据，数据从第二行开始
    $row = 2;
    foreach ($dataArr as $data) {
        $col = 'A';
        foreach ($data as $value) {
            $activeSheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // 设置HTTP头部信息以强制浏览器下载文件
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new WriterXlsx($spreadsheet);

    if (false === $isSave) {
        $writer->save('php://output');
        exit;
    } else {
        $path = $dirPath . '/' . $fileName;
        $writer->save($path);
        return $path;
    }

    // 使用示例
    // $headerArr = ['姓名', '年龄', '性别'];
    // $dataArr = [
    //     ['张三', 30, '男'],
    //     ['李四', 25, '女'],
    //     ['王五', 40, '男']
    // ];
    // exportToExcel($headerArr, $dataArr, '人员信息.xlsx');
}

/**
 * 邮件发送函数 (此代码由ChatGPT-4构建生成)
 *
 * @param string $email 要发送的邮箱地址
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件路径
 * @return bool|string
 */
function sendEmail(string $email, string $subject, string $body, array $attachment = [])
{
    $mail = new \PHPMailer\PHPMailer();

    $mail->CharSet = 'UTF-8';               // 设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();                        // 设定使用SMTP服务
    $mail->SMTPDebug = 0;                   // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
    $mail->SMTPAuth = true;                 // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';              // 使用安全协议
    $mail->Host = 'smtp.163.com';           // SMTP 服务器
    $mail->Port = 465;                      // SMTP服务器的端口号
    $mail->Username = 'devkeep@163.com';    // SMTP服务器用户名
    $mail->Password = 'QSHJEFKMLGLGYFOD';   // SMTP服务器密码
    $mail->setFrom('devkeep@163.com', $subject);    // 发件人邮箱

    // 收件人信息
    $mail->isHTML(true);                    // 设置邮件格式为HTML
    $mail->addAddress($email);
    $mail->Subject = $subject;              // 邮件主题
    $mail->Body = $body;                    // 邮件内容
    $mail->AltBody = $body;                 // 邮件内容（纯文本）

    if (!empty($attachment)) {
        foreach ($attachment as $file) {
            $mail->addAttachment($file);     // 添加附件
        }
    }

    return $mail->Send() ? true : $mail->ErrorInfo;
}

/**
 * 文件打包下载 (此代码由ChatGPT-4构建生成)
 * 
 * @param string $downloadZip 打包后下载的文件名 
 * @param array $list 打包文件组
 * 
 * @return mixed
 */
function addZip(string $downloadZip, array $list, $isSave = false)
{
    if ($isSave)
    {
        // 使用内置ZipArchive库 保存到服务器
        $zip = new \ZipArchive();
        $bool = $zip->open($downloadZip, \ZipArchive::CREATE|\ZipArchive::OVERWRITE);

        if(TRUE === $bool)
        {
            // 循环把文件追加到Zip包
            foreach ($list as $val)
            {
                $zip->addFile($val, basename($val));
            }
        }
        else
        {
            exit('ZipArchive打开失败，错误代码：' . $bool);
        }

        $zip->close();
        return true;
    }


    // 直接下载
    if (class_exists('\ZipStream\Option\Archive') && class_exists('\ZipStream\ZipStream'))
    {

        // -------------------------------------------------------------------------------
        // 使用ZipStream扩展库 下载
        // -------------------------------------------------------------------------------
        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(true);
        $zip = new \ZipStream\ZipStream($downloadZip, $options);

        // 添加文件
        foreach ($list as $v)
        {
            $zip->addFileFromPath(basename($v), $v);
        }

        $zip->finish();
    }
    else
    {
        // -------------------------------------------------------------------------------
        // 使用内置ZipArchive库 下载
        // -------------------------------------------------------------------------------
        $zip = new \ZipArchive();
        $bool = $zip->open($downloadZip, \ZipArchive::CREATE|\ZipArchive::OVERWRITE);

        if(TRUE === $bool)
        {
            foreach ($list as $val)
            {
                // 把文件追加到Zip包并重命名
                // $zip->addFile($val[0]);
                // $zip->renameName($val[0], $val[1]);

                // 把文件追加到Zip包
                $zip->addFile($val, basename($val));
            }
        }
        else
        {
            exit('ZipArchive打开失败，错误代码：' . $bool);
        }

        $zip->close();

        header('Cache-Control: max-age=0');
        header('Content-Description: File Transfer');            
        header('Content-disposition: attachment; filename=' . basename($downloadZip)); 
        header('Content-Type: application/zip');                     // zip格式的
        header('Content-Transfer-Encoding: binary');                 // 二进制文件
        header('Content-Length: ' . filesize($downloadZip));          // 文件大小
        readfile($downloadZip);
    }

    exit();
}


/**
 * 批量更新功能 (此代码由ChatGPT-4构建生成)，原因：TP6里的saveAll方法本质也是循环执行的，因此改进了此方法
 *
 * @param string $tableName 表名
 * @param array $data 要更新的数组
 * @param string $indexKey 索引键名/条件字段
 * @param array $conditionFields 附加的条件字段
 */
function batchUpdate($tableName, $data, $indexKey, $conditionFields = [])
{
    // 检查数据数组是否为空
    if (empty($data)) {
        return false;
    }

    // 初始化变量
    $cases = [];
    $ids = [];
    $params = [];

    // 构建 CASE 语句和收集参数
    foreach ($data as $row) {
        $ids[] = $row[$indexKey];

        foreach (array_keys($row) as $key) {
            if ($key != $indexKey) {
                $cases[$key][] = "WHEN {$indexKey} = ? THEN ?";
                $params[] = $row[$indexKey];
                $params[] = $row[$key];
            }
        }
    }

    // 构建更新语句
    $query = "UPDATE {$tableName} SET ";
    $queryParts = [];
    foreach ($cases as $field => $casePart) {
        $queryParts[] = "{$field} = CASE " . implode(' ', $casePart) . " END";
    }

    // 使用参数绑定来防止 SQL 注入
    $query .= implode(', ', $queryParts);
    $query .= " WHERE {$indexKey} IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";

    // 添加自定义条件
    if (!empty($conditionFields)) {
        foreach ($conditionFields as $field => $value) {
            $query .= " AND {$field} = ?";
            $params[] = $value;
        }
    }

    // 合并参数数组
    $params = array_merge($params, $ids);

    // 执行查询
    return Db::execute($query, $params);
}

/**
 * 二维数组排序
 *
 * @param array $array 排序的数组
 * @param string $keys 要排序的key
 * @param string $sort 排序类型 ASC、DESC
 *
 * @return array
 */
function arrayMultiSort(array $array, string $keys, string $sort = 'asc'): array
{
    $keysValue = [];

    foreach ($array as $k => $v)
    {
        $keysValue[$k] = $v[$keys];
    }

    $orderSort = [
        'asc'  => SORT_ASC,
        'desc' => SORT_DESC,
    ];

    array_multisort($keysValue, $orderSort[$sort], $array);

    return $array;
}

/**
 * 二维数组去重
 *
 * @param array $arr 数组
 * @param string $key 字段
 *
 * @return array
 */
function arrayMultiUnique(array $arr, string $key = 'id'): array
{
    $res = [];

    foreach ($arr as $value)
    {
        if(!isset($res[$value[$key]]))
        {
            $res[$value[$key]] = $value;
        }
    }

    return array_values($res);
}