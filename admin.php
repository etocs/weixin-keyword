<?php
session_start();
require_once 'config.php';

// 验证登录状态
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>关键词管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --hover-color: #45a049;
            --background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        body {
            background: var(--background);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-group {
            gap: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #f8f9fa;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: #f9f9f9;
        }

        .btn-group .btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .modal-content {
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border: none;
            padding: 1rem 1.5rem;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.8rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25);
        }

        @media (max-width: 768px) {
            .admin-card {
                padding: 1rem;
            }

            .table th,
            .table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-card">
            <div class="page-header">
                <h2 class="mb-0">关键词管理</h2>
                <div class="btn-group">
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>退出登录
                    </a>
                </div>
            </div>

            <div class="mb-4">
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKeywordModal">
                        <i class="fas fa-plus me-2"></i>添加关键词
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import me-2"></i>导入关键词
                    </button>
                </div>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>关键词</th>
                        <th>内容</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                        if ($conn->connect_error) {
                            throw new Exception("数据库连接失败: " . $conn->connect_error);
                        }

                        $sql = "SELECT * FROM keywords";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['keyword']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['content']) . "</td>";
                                echo "<td class='d-flex gap-2'>";
                                echo "<a href='edit.php?id=" . $row['id'] . "' class='btn btn-sm btn-warning'>";
                                echo "<i class='fas fa-edit me-2'></i>编辑";
                                echo "</a>";
                                echo "<a href='delete.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger'>";
                                echo "<i class='fas fa-trash me-2'></i>删除";
                                echo "</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center text-muted'>暂无数据</td></tr>";
                        }

                        $result->free();
                        $conn->close();
                    } catch (Exception $e) {
                        echo "<tr><td colspan='3' class='text-center text-danger'>数据加载失败，请联系管理员</td></tr>";
                        error_log($e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 添加关键词 Modal -->
    <div class="modal fade" id="addKeywordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="add.php">
                    <div class="modal-header">
                        <h5 class="modal-title">添加关键词</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="keyword" placeholder="请输入关键词" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="4" placeholder="请输入内容" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 导入关键词 Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="import.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">导入关键词</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="file" accept=".txt" required>
                            <div class="form-text">
                                支持.txt格式文件，每行一个关键词，格式为：关键词,内容
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">导入</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // 表单提交反馈
            $('form').submit(function() {
                $(this).prepend('<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>正在处理...</div>');
            });

            // 模态框关闭时重置表单
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
            });
        });
    </script>
</body>
</html>