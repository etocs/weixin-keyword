<?php
define('IN_APP', true);
session_start();
require_once 'config.php';

// 检查登录状态
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

// 检查POST数据
if (isset($_POST['id'], $_POST['keyword'], $_POST['content'])) {
    try {
        // 创建数据库连接
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }

        // 准备预处理语句
        $stmt = $conn->prepare("UPDATE keywords SET keyword=?, content=? WHERE id=?");
        $stmt->bind_param("ssi", $_POST['keyword'], $_POST['content'], $_POST['id']);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: admin.php");
            exit();
        } else {
            throw new Exception("更新失败: " . $stmt->error);
        }
    } catch (Exception $e) {
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
    <title>更新关键词</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .update-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem auto;
            max-width: 800px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        input, textarea {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25);
        }

        button {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .alert {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .alert-danger {
            background: #fee;
            border: 2px solid #ff4444;
        }

        @media (max-width: 576px) {
            .update-card {
                padding: 1rem;
            }
            
            input, textarea {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="update-card">
                    <h2 class="text-center mb-4 fw-bold" style="color: #4CAF50;">
                        <i class="fas fa-edit me-2"></i>更新关键词
                    </h2>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label for="id">ID</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="id" 
                                   id="id" 
                                   value="<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>"
                                   readonly>
                        </div>

                        <div class="form-group">
                            <label for="keyword">关键词</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="keyword" 
                                   id="keyword" 
                                   required
                                   placeholder="请输入关键词">
                        </div>

                        <div class="form-group">
                            <label for="content">内容</label>
                            <textarea class="form-control" 
                                      name="content" 
                                      id="content" 
                                      rows="4" 
                                      required
                                      placeholder="请输入内容"><?php echo isset($_GET['content']) ? $_GET['content'] : ''; ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" 
                                    class="btn btn-primary fw-bold">
                                <i class="fas fa-save me-2"></i>保存更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 表单提交反馈
            $('form').submit(function() {
                $('.update-card').prepend('<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>正在更新...</div>');
            });
        });
    </script>
</body>
</html>