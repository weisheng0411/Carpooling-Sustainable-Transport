<?php
session_start();
include("conn.php");

//user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

//notification
if (!isset($_GET['id'])) {
    header("Location: notification_page.php");
    exit();
}
$not_id = intval($_GET['id']);


$sql = "UPDATE user_notification 
        SET is_read = 1 
        WHERE user_ID = $user_id 
        AND not_ID = $not_id";

mysqli_query($conn, $sql);
    
header("Location: notification_page.php");
exit();
?>