<?php
// delete.php
define('IN_APP', true);
session_start();

// 包含配置文件
require_once 'config.php';

// 验证登录状态
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

// 验证参数有效性
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("非法请求：参数格式错误");
}

try {
    // 创建数据库连接
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("数据库连接失败: " . $conn->connect_error);
    }

    // 使用预处理语句防止SQL注入
    $stmt = $conn->prepare("DELETE FROM keywords WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    
    // 执行删除操作
    if ($stmt->execute()) {
        // 记录操作日志（可选）
        $log = sprintf(
            "[%s] 用户 %s 删除记录 ID:%s",
            date('Y-m-d H:i:s'),
            $_SESSION['username'],
            $_GET['id']
        );
        file_put_contents('operation.log', $log.PHP_EOL, FILE_APPEND);
    } else {
        throw new Exception("删除失败: " . $stmt->error);
    }

    // 关闭连接
    $stmt->close();
    $conn->close();

    // 重定向回管理页面
    header("Location: admin.php");
    exit();

} catch (Exception $e) {
    // 错误处理
    error_log($e->getMessage());
    die("操作失败，请联系管理员");
}
?>

