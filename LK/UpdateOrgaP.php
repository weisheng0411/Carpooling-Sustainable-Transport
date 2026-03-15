<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event Organizer Profile</title>
</head>
<body>
<?php
if (isset($_POST['confirmBtn'])) {
    include('conn.php');

    $sql = "UPDATE user_acc
            JOIN organizer ON user_acc.user_id = organizer.User_ID
            SET
                user_acc.apu_id = '".$_POST['TPNumber']."',
                user_acc.username = '".$_POST['username']."',
                user_acc.email = '".$_POST['email']."',
                user_acc.phone_number = '".$_POST['phoneNumber']."',
                organizer.Description = '".$_POST['details']."'
            WHERE user_acc.user_id = ".$_POST['id'];

    if (mysqli_query($con, $sql)) {
        mysqli_close($con);
        echo "<script>
            alert('Record Updated');
            window.location.href='OrganizerProfile.php';
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
