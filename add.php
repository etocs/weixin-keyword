<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
}
if (isset($_POST['keyword']) && isset($_POST['content'])) {
    $keyword = $_POST['keyword'];
    $content = $_POST['content'];
    $conn = mysqli_connect("mysql.ct8.pl", "m50503_wx", "Aa123456789", "m50503_wx");
    $sql = "INSERT INTO keywords (keyword, content) VALUES ('$keyword', '$content')";
    mysqli_query($conn, $sql);
    header("Location: admin.php");
}
?>
