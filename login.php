<?php
session_start();
require_once 'config.php';

// 登录尝试限制
if (($_SESSION['login_attempts'] ?? 0) > 5) {
    die("登录尝试次数过多，请15分钟后再试");
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败");
        }

        // 使用预处理语句
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // 自动升级哈希算法
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param("si", $newHash, $user['id']);
                    $update->execute();
                }

                // 重置会话
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

                header("Location: admin.php");
                exit();
            } else {
                throw new Exception("用户名或密码错误");
            }
        } else {
            throw new Exception("用户名或密码错误");
        }
    } catch (Exception $e) {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $error = $e->getMessage();
    } finally {
        if (isset($conn)) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>用户登录</title>
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

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            margin: 1rem;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .form-control {
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #757575;
        }

        .btn-login {
            background: var(--primary-color);
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .alert-danger {
            border-radius: 10px;
            padding: 1rem;
            background: #fee;
            border: 2px solid #ff4444;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem;
                margin: 0.5rem;
            }
            
            .form-control {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="login-card">
                    <h2 class="text-center mb-4 fw-bold" style="color: var(--primary-color);">
                        <i class="fas fa-comment-dots me-2"></i>登录
                    </h2>
                    
                    <form method="post">
                        <!-- 用户名输入 -->
                        <div class="mb-4 position-relative">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   class="form-control ps-4" 
                                   name="username" 
                                   placeholder="请输入用户名"
                                   required>
                        </div>

                        <!-- 密码输入 -->
                        <div class="mb-4 position-relative">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   class="form-control ps-4" 
                                   name="password" 
                                   placeholder="请输入密码"
                                   required>
                        </div>

                        <!-- 登录按钮 -->
                        <button type="submit" 
                                name="login" 
                                class="btn btn-login w-100 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i>立即登录
                        </button>
                    </form>

                    <!-- 错误提示 -->
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger mt-4">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 辅助链接 -->
                    <div class="text-center mt-4">
                        <a href="#" class="text-decoration-none text-secondary">
                            <i class="fas fa-question-circle me-2"></i>忘记密码？
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载动画 -->
    <div class="loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 表单提交动画
            $('form').submit(function() {
                $('.loader').show();
                $('button[type="submit"]').prop('disabled', true);
            });

            // 输入框动态效果
            $('.form-control').focus(function() {
                $(this).parent().find('.input-icon').css('color', var(--primary-color));
            }).blur(function() {
                $(this).parent().find('.input-icon').css('color', '#757575');
            });
        });
    </script>
</body>
</html>