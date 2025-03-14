<?php
require_once __DIR__ . '/config.php';

// 验证登录状态
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// 参数验证
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die(json_encode(['status' => 'error', 'message' => '非法请求参数']));
}

$id = (int)$_GET['id'];

try {
    // 数据库连接
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("数据库连接失败: " . $conn->connect_error);
    }

    // 预处理查询
    $stmt = $conn->prepare("SELECT * FROM keywords WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("指定记录不存在");
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    die("系统错误，请联系管理员");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>编辑关键词 - 管理系统</title>
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
            padding: 1rem;
        }

        .edit-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-card">
            <h2 class="text-center mb-4 fw-bold text-success">
                <i class="fas fa-edit me-2"></i>编辑关键词
            </h2>
            
            <form method="post" action="update.php" id="editForm">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">关键词</label>
                    <input type="text" 
                           class="form-control" 
                           name="keyword" 
                           value="<?= htmlspecialchars($row['keyword'], ENT_QUOTES) ?>" 
                           required
                           pattern=".{2,50}"
                           title="请输入2-50个字符">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">回复内容</label>
                    <textarea class="form-control" 
                              name="content" 
                              rows="5"
                              required
                              minlength="5"
                              placeholder="请输入详细回复内容"><?= htmlspecialchars($row['content'], ENT_QUOTES) ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>返回列表
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>保存修改
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 表单提交处理
            $('#editForm').submit(function(e) {
                e.preventDefault();
                
                // 显示加载状态
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>保存中...');

                // 异步提交
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        window.location.href = 'admin.php?success=1';
                    },
                    error: function(xhr) {
                        alert('保存失败: ' + xhr.responseText);
                        $submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>保存修改');
                    }
                });
            });
        });
    </script>
</body>
</html>