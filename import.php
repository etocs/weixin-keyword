<?php
session_start();
require_once 'config.php';

define('IN_APP', true);

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['name'];
    $filetmp = $file['tmp_name'];
    $filetype = $file['type'];
    $filesize = $file['size'];

    // 检查文件类型和大小
    if ($filetype != "text/plain" || $filesize > 500000) {
        die("错误：只允许上传不超过500KB的文本文件！");
    }

    // 创建数据库连接
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }

    // 读取文件内容
    $lines = file($filetmp);
    $insertCount = 0;

    foreach ($lines as $line) {
        $data = explode(",", $line);
        if (count($data) < 2) {
            continue;
        }

        $keyword = trim($data[0]);
        $content = trim($data[1]);

        // 使用预处理语句插入数据
        $stmt = $conn->prepare("INSERT INTO keywords (keyword, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $keyword, $content);
        if ($stmt->execute()) {
            $insertCount++;
        } else {
            // 记录插入失败的错误
            error_log("插入失败：关键词 = $keyword，内容 = $content");
        }
    }

    // 关闭数据库连接
    $stmt->close();
    $conn->close();

    // 记录上传日志
    $log = sprintf(
        "[%s] 用户上传文件 %s，成功插入 %d 条记录",
        date('Y-m-d H:i:s'),
        $filename,
        $insertCount
    );
    file_put_contents('upload.log', $log . PHP_EOL, FILE_APPEND);

    // 重定向到管理页面
    header("Location: admin.php");
    exit();
}
?>