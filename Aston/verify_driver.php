<?php
header("Content-Type: application/json; charset=UTF-8");

$mysqli = new mysqli("localhost","root","","wdd");
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
  exit;
}

$driverId = $_POST["driver_id"] ?? "";
$action   = $_POST["action"] ?? "";

$driverId = trim($driverId);
$action   = trim($action);

if ($driverId === "" || !ctype_digit($driverId) || !in_array($action, ["approve","reject"], true)) {
  echo json_encode(["success"=>false,"message"=>"Invalid request"]);
  exit;
}

$newStatus = ($action === "approve") ? "pass" : "reject";

$stmt = $mysqli->prepare("UPDATE driver SET Status=? WHERE Driver_ID=? AND Status='pending'");
$stmt->bind_param("si", $newStatus, $driverId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo json_encode(["success"=>true,"message"=>"Driver ID $driverId has been $action"."d."]);
} else {
  echo json_encode(["success"=>false,"message"=>"Driver not found, or not pending anymore."]);
}

$stmt->close();
$mysqli->close();
