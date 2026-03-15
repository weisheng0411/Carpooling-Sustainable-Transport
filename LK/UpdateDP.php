<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Driver Profile</title>
</head>
<body>
<?php
if (isset($_POST['confirmBtn'])) {
    include('conn.php');

    $photo_sql = "";
    $files = ['ICPhoto', 'LicensePhoto'];
    $dbFields = ['IC_Photo_URL', 'License_Photo_URL'];

    for ($i = 0; $i < count($files); $i++) {
        $inputName = $files[$i];
        $dbField = $dbFields[$i];

        if (!empty($_FILES[$inputName]['name'])) {
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/ASS-WDD/uploads/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES[$inputName]['name']);
            $target_file = $target_dir . $file_name;
            
            $db_file_path = "uploads/" . $file_name;

            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $target_file)) {
                $photo_sql .= ", driver.$dbField = '$db_file_path'";
            }
        }
    }

    $sql = "UPDATE user_acc
            JOIN driver ON user_acc.user_id = driver.user_id
            SET
                user_acc.apu_id = '".$_POST['TPNumber']."',
                user_acc.name = '".$_POST['FName']."',
                user_acc.phone_number = '".$_POST['phoneNumber']."',
                user_acc.email = '".$_POST['email']."',
                driver.Car_Model = '".$_POST['carModel']."',
                driver.Plate_Number = '".$_POST['plate']."',
                driver.Seat_Available = '".$_POST['seat']."',
                driver.License = '".$_POST['license']."'".
                $photo_sql."
            WHERE user_acc.user_id = ".$_POST['id'];

    if (mysqli_query($con, $sql)) {
        mysqli_close($con);
        echo "<script>
            alert('Record Updated');
            window.location.href='DriverProfile.php';
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
