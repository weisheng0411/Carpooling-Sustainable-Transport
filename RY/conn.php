<?php
$servername="localhost";
$username="root";
$password="";
$database="wdd";

$conn = mysqli_connect ("$servername","$username","$password","$database");

if (mysqli_connect_error()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

?>