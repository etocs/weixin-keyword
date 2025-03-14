<?php
// 开启输出缓冲
ob_start();

// 会话安全配置
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,       // 24小时有效期
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,          // 启用HTTPS时设置为true
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// 安全头设置
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// 数据库配置
define('DB_HOST', 'mysql.ct8.pl');
define('DB_USER', 'm50503_wx');
define('DB_PASSWORD', 'Aa123456789');
define('DB_NAME', 'm50503_wx');

// 应用常量
define('IN_APP', true);

// 会话有效期检查（30分钟）
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}
$_SESSION['last_activity'] = time();