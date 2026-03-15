<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
$mysqli = new mysqli("127.0.0.1","root","","wdd");
if ($mysqli->connect_error) die("DB error");

if (!isset($_SESSION["user_id"])) die("Not logged in");
$user_id = (int)$_SESSION["user_id"];

$field = $_POST["field"] ?? "";
$value = trim($_POST["value"] ?? "");

$allowed = ["username","email","password","phone_number"];
if (!in_array($field, $allowed, true)) die("Invalid field");
if ($value === "") die("Value cannot be empty");

if ($field === "password") $value = password_hash($value, PASSWORD_DEFAULT);

$sql = "UPDATE user_acc SET $field = ? WHERE user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("si", $value, $user_id);
$stmt->execute();

$stmt->close();
$mysqli->close();

echo "Updated successfully";
