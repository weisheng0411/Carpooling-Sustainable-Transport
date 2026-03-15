<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$sql = "UPDATE user_notification 
        SET is_read = 1
        WHERE user_ID = $user_id";

if (mysqli_query($conn, $sql)) {
    echo "<script>
            alert('All notifications marked as read!'); 
            window.location.href='notification_page.php';
          </script>";
} else {
    echo "Error updating record: " . mysqli_error($connect);
}
?>