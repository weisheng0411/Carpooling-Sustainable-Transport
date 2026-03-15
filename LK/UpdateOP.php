<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
</head>
<body>
<?php
if (isset($_POST['confirmBtn'])) {
    include('conn.php');

    $photo_sql = "";

    if (!empty($_FILES['photo']['name'])) {

        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/ASS-WDD/uploads/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['photo']['name']);
        $target_file = $target_dir . $file_name;

        $db_file_path = "uploads/" . $file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_sql = ", photo_url = '$db_file_path'";
        }
    }

    $sql = "UPDATE user_acc SET
            apu_id = '".$_POST['TPNumber']."',
            name = '".$_POST['Name']."',
            username = '".$_POST['Username']."',
            password = '".$_POST['Password']."',
            email = '".$_POST['Email']."',
            phone_number = '".$_POST['PhoneNumber']."',
            gender = '".$_POST['gender']."'"
            . $photo_sql . "
            WHERE user_id = ".$_POST['id'];

    if (mysqli_query($con, $sql)) {
        mysqli_close($con);
        echo "<script>
            alert('Record Updated');
            window.location.href='OwnProfile.php';
        </script>";
    } else {
        echo "<script>
            alert('Failed to update');
            window.location.href='EditProfile.php';
        </script>";
    }
}
?>
</body>
</html>
