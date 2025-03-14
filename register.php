<?php
// register.php
define('IN_APP', true);
require_once 'config.php';

session_start();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 输入验证
    if (empty($username)) {
        $errors[] = "用户名不能为空";
    } elseif (strlen($username) < 4) {
        $errors[] = "用户名至少4个字符";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "只能包含字母、数字和下划线";
    }

    if (empty($password)) {
        $errors[] = "密码不能为空";
    } elseif (strlen($password) < 12) {
        $errors[] = "密码至少12个字符";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "必须包含至少一个大写字母";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "必须包含至少一个小写字母";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "必须包含至少一个数字";
    } elseif (!preg_match('/[\W]/', $password)) {
        $errors[] = "必须包含至少一个特殊字符";
    }

    if ($password !== $confirm_password) {
        $errors[] = "两次输入的密码不一致";
    }

    if (empty($errors)) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("数据库连接失败");
            }

            // 检查用户名是否存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = "用户名已存在";
            } else {
                // 哈希密码
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 插入新用户
                $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $insert->bind_param("ss", $username, $hashed_password);

                if ($insert->execute()) {
                    $success = "注册成功！3秒后自动跳转到登录页面";
                    header("Refresh:3; url=login.php");
                } else {
                    throw new Exception("注册失败，请稍后重试");
                }
            }

            $conn->close();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>用户注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --hover-color: #45a049;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            margin: 1rem;
            max-width: 500px;
        }

        .password-rules {
            font-size: 0.9rem;
            color: #666;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .strength-meter {
            height: 5px;
            margin-top: 0.5rem;
            background: #ddd;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-fill {
            width: 0;
            height: 100%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <h2 class="text-center mb-4 fw-bold" style="color: var(--primary-color);">
                        <i class="fas fa-user-plus me-2"></i>新用户注册
                    </h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="registerForm">
                        <!-- 用户名输入 -->
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="username" 
                                       required
                                       pattern="[a-zA-Z0-9_]{4,}"
                                       title="4-20位字母、数字或下划线">
                            </div>
                        </div>

                        <!-- 密码输入 -->
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       name="password" 
                                       id="password"
                                       required
                                       minlength="12">
                            </div>
                            <div class="strength-meter">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                        </div>

                        <!-- 确认密码 -->
                        <div class="mb-4">
                            <label class="form-label">确认密码</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       name="confirm_password" 
                                       required
                                       minlength="12">
                            </div>
                        </div>

                        <!-- 密码规则提示 -->
                        <div class="password-rules">
                            <h6><i class="fas fa-shield-alt me-2"></i>密码必须包含：</h6>
                            <ul class="list-unstyled mb-0">
                                <li id="ruleLength"><i class="fas fa-check me-2"></i>至少12个字符</li>
                                <li id="ruleUpper"><i class="fas fa-check me-2"></i>至少一个大写字母</li>
                                <li id="ruleLower"><i class="fas fa-check me-2"></i>至少一个小写字母</li>
                                <li id="ruleNumber"><i class="fas fa-check me-2"></i>至少一个数字</li>
                                <li id="ruleSpecial"><i class="fas fa-check me-2"></i>至少一个特殊字符</li>
                            </ul>
                        </div>

                        <!-- 注册按钮 -->
                        <button type="submit" 
                                name="register" 
                                class="btn btn-primary w-100 fw-bold py-2">
                            <i class="fas fa-user-plus me-2"></i>立即注册
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <span>已有账号？</span>
                        <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--primary-color);">
                            <i class="fas fa-sign-in-alt me-2"></i>立即登录
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 密码强度实时检测
            $('#password').on('input', function() {
                const password = $(this).val();
                let strength = 0;

                // 长度规则
                const hasMinLength = password.length >= 12;
                $('#ruleLength i').toggleClass('fa-check text-success', hasMinLength);

                // 大写字母
                const hasUpper = /[A-Z]/.test(password);
                $('#ruleUpper i').toggleClass('fa-check text-success', hasUpper);

                // 小写字母
                const hasLower = /[a-z]/.test(password);
                $('#ruleLower i').toggleClass('fa-check text-success', hasLower);

                // 数字
                const hasNumber = /[0-9]/.test(password);
                $('#ruleNumber i').toggleClass('fa-check text-success', hasNumber);

                // 特殊字符
                const hasSpecial = /[\W_]/.test(password);
                $('#ruleSpecial i').toggleClass('fa-check text-success', hasSpecial);

                // 计算强度值
                strength += hasMinLength ? 1 : 0;
                strength += hasUpper ? 1 : 0;
                strength += hasLower ? 1 : 0;
                strength += hasNumber ? 1 : 0;
                strength += hasSpecial ? 1 : 0;

                // 更新强度条
                const width = (strength / 5) * 100;
                $('#strengthFill').css('width', width + '%')
                    .css('background-color', getStrengthColor(strength));
            });

            function getStrengthColor(strength) {
                switch (strength) {
                    case 0: return '#dc3545';
                    case 1: return '#dc3545';
                    case 2: return '#ffc107';
                    case 3: return '#28a745';
                    case 4: return '#28a745';
                    case 5: return '#28a745';
                }
            }
        });
    </script>
</body>
</html>