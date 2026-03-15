<?php
session_start();

// Database connection configuration
$host = 'localhost';
$dbname = 'wdd';
$username = 'root'; // Please modify according to actual situation
$password = ''; // Please modify according to actual situation

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
$user_id = null;
$user_info = null;
$already_registered = false; // Add this variable to check if already registered
$driver_status = null; // Add variable to store driver status

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user information from user_acc table
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_acc WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Failed to get user information: " . $e->getMessage());
    }
    
    // Check if user has already registered as a driver - check driver table
    if ($user_info) {
        try {
            $check_stmt = $pdo->prepare("SELECT * FROM driver WHERE user_id = :user_id");
            $check_stmt->execute(['user_id' => $user_id]);
            $driver_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($driver_info) {
                $already_registered = true;
                $driver_status = $driver_info['Status'] ?? null; // Get driver status
            }
        } catch (PDOException $e) {
            // Log error but don't prevent page loading
            error_log("Error checking driver registration status: " . $e->getMessage());
        }
    }
}

// Handle form submission
$registration_success = false;
$errors = [];

// If user is already registered, don't allow resubmission
if ($already_registered) {
    $errors[] = "You have already registered as a driver. You cannot register again.";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_info) {
   
    $fullName = $user_info['name'] ?? '';
    $email = $user_info['email'] ?? '';
    $phoneNumber = $user_info['phone_number'] ?? '';
    $tpNumber = $user_info['apu_id'] ?? '';
    
   
    $license_number = $_POST['licenseNumber'] ?? '';
    $car_model = $_POST['vehicleModel'] ?? '';
    $car_plate = $_POST['carPlate'] ?? '';
    $seats_available = $_POST['seatsAvailable'] ?? 4;
    
    $car_color = $_POST['carColor'] ?? '';

    // In validation section, add color validation:
    if (empty($car_color)) {
        $errors[] = "Car color is required";
    }
    if (empty($license_number)) {
        $errors[] = "License number is required";
    }
    if (empty($car_model)) {
        $errors[] = "Vehicle model is required";
    }
    if (empty($car_plate)) {
        $errors[] = "Car plate number is required";
    }
    if (empty($seats_available) || $seats_available < 1 || $seats_available > 10) {
        $errors[] = "Seats available must be between 1 and 10";
    }
    
    // Handle file uploads
    $ic_photo_url = null;
    $license_photo_url = null;

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ASS-WDD/uploads/';
    $upload_url = 'uploads/';

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    /* IC Photo */
    if (isset($_FILES['icPhoto']) && $_FILES['icPhoto']['error'] === UPLOAD_ERR_OK) {

        $ic_filename = uniqid() . '_' . basename($_FILES['icPhoto']['name']);
        $ic_target_path = $upload_dir . $ic_filename;
        $file_type = $_FILES['icPhoto']['type'];

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['icPhoto']['tmp_name'], $ic_target_path)) {
                $ic_photo_url = $upload_url . $ic_filename;
            } else {
                $errors[] = "Failed to upload IC photo";
            }
        } else {
            $errors[] = "Invalid file type for IC photo";
        }

    } else {
        $errors[] = "IC photo is required";
    }

    /* License Photo */
    if (isset($_FILES['licensePhoto']) && $_FILES['licensePhoto']['error'] === UPLOAD_ERR_OK) {

        $license_filename = uniqid() . '_' . basename($_FILES['licensePhoto']['name']);
        $license_target_path = $upload_dir . $license_filename;
        $file_type = $_FILES['licensePhoto']['type'];

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['licensePhoto']['tmp_name'], $license_target_path)) {
                $license_photo_url = $upload_url . $license_filename;
            } else {
                $errors[] = "Failed to upload license photo";
            }
        } else {
            $errors[] = "Invalid file type for license photo";
        }

    } else {
        $errors[] = "License photo is required";
    }

    
    // If no errors, insert data into driver table
    if (empty($errors) && $user_info) {
        try {
            // Check again if user is already registered in driver table (prevent concurrent requests)
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM driver WHERE user_id = :user_id");
            $check_stmt->execute(['user_id' => $user_id]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $errors[] = "You have already registered as a driver. You cannot register again.";
            } else {
                $sql = "INSERT INTO driver (License, Car_Model, Car_Color, Plate_Number, Seat_Available, IC_Photo_URL, License_Photo_URL, Status, user_id) 
                        VALUES (:license, :car_model, :car_color, :plate_number, :seats_available, :ic_photo_url, :license_photo_url, :status, :user_id)";
                
                $stmt = $pdo->prepare($sql);
                
                $result = $stmt->execute([
                    ':license' => $license_number,
                    ':car_model' => $car_model,
                    ':car_color' => $car_color,
                    ':plate_number' => $car_plate,
                    ':seats_available' => $seats_available,
                    ':ic_photo_url' => $ic_photo_url,
                    ':license_photo_url' => $license_photo_url, 
                    ':status' => 'pending',
                    ':user_id' => $user_id
                ]);
                
                if ($result) {
                    $registration_success = true;
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// If user is not logged in, redirect to login page
if (!$user_info) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Driver - EcoCommute</title>
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            --border-radius: 12px;
            --admin-blue: #2196f3;
            --admin-blue-light: #64b5f6;
            --admin-blue-dark: #1976d2;
            --primary-blue: #1a73e8;
            --secondary-blue: #4285f4;
            --accent-orange: #ff6d01;
            --error-red: #d93025;
            --success-green: #0f9d58;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        button, .btn-link {
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* ========== Sidebar Design ========== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--eco-green) 0%, #1b5e20 100%);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: white;
            font-size: 22px;
            font-weight: 700;
            text-decoration: none;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--eco-green);
            font-size: 20px;
        }

        .sidebar-nav {
            padding: 0 20px;
            flex-grow: 1;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-title {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            padding-left: 15px;
        }

        .nav-btn {
            width: 100%;
            padding: 14px 20px;
            background: transparent;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 8px;
            border-radius: 10px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .nav-btn .icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* ========== Main Content Area ========== */
        .main {
            margin-left: 300px;
            padding: 30px;
            width: calc(100% - 300px);
            max-width: 1200px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title h2 {
            font-size: 28px;
            color: var(--text-dark);
            margin: 0 0 5px 0;
        }

        .page-title p {
            color: var(--text-light);
            margin: 0;
            font-size: 15px;
        }

        .top-right {
            /* Empty container for layout purposes */
        }
        
        hr {
            border: none;
            height: 1px;
            background: #ddd;
            margin: 20px 0 30px 0;
        }
        
        /* ========== Registration Form ========== */
        .form-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .register-form-box {
            background-color: var(--card-bg);
            width: 900px;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-top: 5px solid var(--light-green);
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 32px;
            margin: 0 0 10px 0;
            color: var(--text-dark);
        }

        .form-subtitle {
            color: var(--text-light);
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .progress-steps {
            display: flex;
            margin-bottom: 40px;
            position: relative;
            justify-content: center;
        }

        .progress-steps::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            max-width: 150px;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #9e9e9e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 8px;
            border: 3px solid white;
        }

        .step.active .step-number {
            background-color: var(--light-green);
            color: white;
        }

        .step.completed .step-number {
            background-color: var(--eco-green);
            color: white;
        }

        .step-label {
            font-size: 14px;
            color: #9e9e9e;
            text-align: center;
        }

        .step.active .step-label {
            color: var(--text-dark);
            font-weight: 600;
        }

        .form-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 22px;
            margin: 0 0 20px 0;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: "";
            display: block;
            width: 8px;
            height: 24px;
            background: var(--light-green);
            border-radius: 4px;
        }

        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            flex: 100%;
        }

        label {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-medium);
        }

        .required::after {
            content: " *";
            color: var(--error-red);
        }

        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="tel"],
        select {
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #dadce0;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        input.error {
            border-color: var(--error-red);
        }

        input.success {
            border-color: var(--success-green);
        }

        .error-message {
            color: var(--error-red);
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            color: var(--success-green);
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .file-upload-container {
            display: flex;
            gap: 20px;
        }

        .file-upload-box {
            flex: 1;
            border: 2px dashed #c8e6c9;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
            background-color: #f8fff8;
        }

        .file-upload-box:hover {
            border-color: var(--light-green);
            background-color: #f1f8e9;
        }

        .file-upload-box.drag-over {
            border-color: var(--light-green);
            background-color: #f1f8e9;
        }

        .file-icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: var(--text-medium);
        }

        .file-upload-box p {
            margin: 0 0 10px 0;
            color: var(--text-light);
        }

        .file-upload-box .file-name {
            color: var(--eco-green);
            font-weight: 500;
            margin-top: 10px;
            font-size: 14px;
        }

        .upload-hint {
            font-size: 12px;
            color: #9e9e9e;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary {
            background-color: #f1f3f4;
            color: var(--text-dark);
            border: 1px solid #dadce0;
        }

        .btn-secondary:hover {
            background-color: #e8eaed;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .form-note {
            background-color: #f8fff8;
            border-left: 4px solid var(--light-green);
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 14px;
            color: var(--text-medium);
        }

        .form-note p {
            margin: 0 0 10px 0;
        }

        .form-note ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-note li {
            margin-bottom: 5px;
        }

        /* Read-only field styles */
        .readonly-input {
            background-color: #f5f5f5 !important;
            border-color: #e0e0e0 !important;
            color: #666 !important;
            cursor: not-allowed;
        }

        .readonly-label {
            color: #9e9e9e !important;
        }

        /* Success and error message styles */
        .success-alert {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .error-alert ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        
        .error-alert li {
            margin-bottom: 5px;
        }

        /* Already registered message styles */
        .already-registered-message {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #f8f9f8, white);
            border-radius: 12px;
            border: 2px solid #c8e6c9;
            margin-top: 20px;
        }
        
        .already-registered-message h2 {
            color: var(--text-dark);
            margin-bottom: 20px;
        }
        
        .already-registered-message p {
            color: var(--text-medium);
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .already-registered-icon {
            font-size: 64px;
            color: var(--light-green);
            margin-bottom: 20px;
        }
        
        /* Status badge styles */
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 10px 0;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff3e0, #ffcc80);
            color: #ef6c00;
            border: 1px solid #ffb74d;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: var(--eco-green);
            border: 1px solid #a5d6a7;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            .main {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 25px;
            }
            
            .register-form-box {
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-header .sidebar-logo span,
            .nav-title,
            .nav-btn span:not(.icon) {
                display: none;
            }
            
            .sidebar-header {
                padding: 20px 10px;
            }
            
            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            
            .nav-btn {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-btn .icon {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main {
                margin-left: 100px;
                width: calc(100% - 100px);
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .file-upload-container {
                flex-direction: column;
            }
            
            .progress-steps::before {
                left: 30px;
                right: 30px;
            }
        }

        @media (max-width: 600px) {
            .main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-header .sidebar-logo span,
            .nav-title,
            .nav-btn span:not(.icon) {
                display: block;
            }
            
            .sidebar-header {
                padding: 25px 20px;
            }
            
            .nav-btn {
                justify-content: flex-start;
                padding: 14px 20px;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .top-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .register-form-box {
                padding: 25px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
            }
            
            .progress-steps::before {
                display: none;
            }
            
            .progress-steps {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            
            .step {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="homepage.php" class="sidebar-logo">
                    <div class="logo-icon">🌱</div>
                    <span>GOBy</span>
                </a>
            </div>
            
            <div class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Navigation</div>
                    <a href="homepage.php" class="nav-btn">
                        <span class="icon">🏠</span>
                        <span>Homepage</span>
                    </a>
                    <a href="event.php" class="nav-btn">
                        <span class="icon">📅</span>
                        <span>Event</span>
                    </a>
                    <a href="../Aston/Chlng.php" class="nav-btn">
                        <span class="icon">🏆</span>
                        <span>Challenge</span>
                    </a>
                    <a href="reward_page.php" class="nav-btn">
                        <span class="icon">📊</span>
                        <span>Reward</span>
                    </a>
                    <a href="../Lk/JourneyPlanner.php" class="nav-btn">
                        <span class="icon">🗺️</span>
                        <span>Journey Planner</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Community</div>
                    <a href="../LK/CarpoolList.php" class="nav-btn">
                        <span class="icon">👥</span>
                        <span>Carpool List</span>
                    </a>
                    <a href="../RY/ranking_page.php" class="nav-btn">
                        <span class="icon">🏆</span>
                        <span>Ranking</span>
                    </a>
                    <a href="../RY/news_page.php" class="nav-btn">
                        <span class="icon">📰</span>
                        <span>News</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Tools</div>
                    <a href="../LK/SmartParking.php" class="nav-btn">
                        <span class="icon">🅿️</span>
                        <span>Smart Parking</span>
                    </a>
                    <a href="../LK/Ahm.php" class="nav-btn">
                        <span class="icon">🎯</span>
                        <span>Achievement</span>
                    </a>
                    <a href="../RY/statistics.php" class="nav-btn">
                        <span class="icon">🎁</span>
                        <span>Statistic/Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Registration</div>
                    <a href="register_event_organizer.php" class="nav-btn">
                        <span class="icon">📝</span>
                        <span>Register Event Organizer</span>
                    </a>
                    <a href="register_driver.php" class="nav-btn active">
                        <span class="icon">🚗</span>
                        <span>Register Driver</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Account</div>
                    <a href="../Aston/profile.php" class="nav-btn">
                        <span class="icon">👤</span>
                        <span>Profile</span>
                    </a>
                    <a href="logout.php" class="nav-btn">
                        <span class="icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Main Content Area -->
        <main class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h2>Register Driver</h2>
                    <p>Become an EcoCommute driver and start your green journey today! 🚗</p>
                </div>  
                
                <!-- Top right section -->
                <div class="top-right">
                    <!-- This area is empty, no notification or avatar icons -->
                </div>
            </div>
           
            <hr>
            
            <!-- Display success or error messages -->
            <?php if ($registration_success): ?>
            <div class="success-alert">
                <span style="font-size: 24px;">✅</span>
                <div>
                    <strong>Registration Successful!</strong><br>
                    Your driver application has been submitted and will be reviewed within 3-5 business days.
                    You will receive an email notification once your application is approved.
                </div>
            </div>
            <?php elseif (!empty($errors)): ?>
            <div class="error-alert">
                <strong>⚠️ Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- If user is already registered, show message instead of form -->
            <?php if ($already_registered && !$registration_success): ?>
            <div class="already-registered-message">
                <div class="already-registered-icon">
                    <i class="fas fa-car"></i>
                </div>
                <h2>You're Already Registered!</h2>
                <p>You have already registered as a driver. You cannot register again.</p>
                
                <?php if ($driver_status): ?>
                <p>Your current registration status: 
                    <span class="status-badge status-<?php echo strtolower($driver_status); ?>">
                        <?php echo htmlspecialchars($driver_status); ?>
                    </span>
                </p>
                <?php endif; ?>
                
                <p>If you need to update your information or have any questions, please contact our support team.</p>
                <a href="homepage.php" class="btn btn-primary" style="margin-top: 20px;">
                    <span>Go to Homepage</span>
                </a>
            </div>
            <?php elseif (!$registration_success): ?>
            <!-- Registration Form -->
            <div class="form-container">
                <div class="register-form-box">
                    <div class="form-header">
                        <h2>Driver Registration Form</h2>
                        <p class="form-subtitle">Complete the form below to register as an EcoCommute driver. All fields marked with * are required.</p>
                    </div>
                    
                    <!-- Progress indicator -->
                    <div class="progress-steps">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-label">Personal Info</div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-label">Vehicle Info</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-label">Upload Files</div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-label">Complete</div>
                        </div>
                    </div>
                    
                    <form id="driverRegistrationForm" method="POST" enctype="multipart/form-data">
                        
                        <div class="form-section">
                            <h3 class="section-title">Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tpNumber" class="required readonly-label">TP Number</label>
                                    <input type="text" id="tpNumber" name="tpNumber" class="readonly-input" 
                                           value="<?php echo htmlspecialchars($user_info['apu_id'] ?? ''); ?>" readonly>
                                    <div class="error-message" id="tpError">TP number cannot be empty</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fullName" class="required readonly-label">Full Name</label>
                                    <input type="text" id="fullName" name="fullName" class="readonly-input" 
                                           value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" readonly>
                                    <div class="error-message" id="nameError">Full name cannot be empty</div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phoneNumber" class="required readonly-label">Phone Number</label>
                                    <input type="tel" id="phoneNumber" name="phoneNumber" class="readonly-input" 
                                           value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>" readonly>
                                    <div class="error-message" id="phoneError">Please enter a valid 10-11 digit phone number</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="required readonly-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="readonly-input" 
                                           value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" readonly>
                                    <div class="error-message" id="emailError">Please enter a valid email address</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vehicle Information Section -->
                        <div class="form-section">
                            <h3 class="section-title">Vehicle Information</h3>
                            
                            <!-- Add a new form group in the "Vehicle Information" section form-row -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehicleModel" class="required">Vehicle Model</label>
                                    <input type="text" id="vehicleModel" name="vehicleModel" placeholder="e.g. Toyota Camry" 
                                        value="<?php echo htmlspecialchars($_POST['vehicleModel'] ?? ''); ?>" required>
                                    <div class="error-message" id="vehicleError">Vehicle model cannot be empty</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="carColor" class="required">Vehicle Color</label>
                                    <input type="text" id="carColor" name="carColor" placeholder="e.g. Red, Blue, White" 
                                        value="<?php echo htmlspecialchars($_POST['carColor'] ?? ''); ?>" required>
                                    <div class="error-message" id="colorError">Vehicle color cannot be empty</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="carPlate" class="required">Car Plate Number</label>
                                    <input type="text" id="carPlate" name="carPlate" placeholder="e.g. ABC1234" 
                                        value="<?php echo htmlspecialchars($_POST['carPlate'] ?? ''); ?>" required>
                                    <div class="error-message" id="plateError">Please enter a valid car plate number</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="seatsAvailable" class="required">Seats Available</label>
                                    <input type="number" id="seatsAvailable" name="seatsAvailable" min="1" max="10" 
                                        value="<?php echo htmlspecialchars($_POST['seatsAvailable'] ?? 4); ?>" required>
                                    <div class="error-message" id="seatsError">Seats available must be between 1 and 10</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full">
                                    <label for="licenseNumber" class="required">License Number</label>
                                    <input type="text" id="licenseNumber" name="licenseNumber" placeholder="Enter driver's license number" 
                                        value="<?php echo htmlspecialchars($_POST['licenseNumber'] ?? ''); ?>" required>
                                    <div class="error-message" id="licenseError">License number cannot be empty</div>
                                </div>
                            </div>
                        
                        <!-- File Upload Section -->
                        <div class="form-section">
                            <h3 class="section-title">Document Upload</h3>
                            <p>Please upload clear photos or scanned copies of the following documents. Supported formats: JPG, PNG, PDF. Maximum file size: 5MB each.</p>
                            
                            <div class="file-upload-container">
                                <div class="file-upload-box" id="icUploadBox">
                                    <div class="file-icon">📄</div>
                                    <p><strong>IC Photo</strong></p>
                                    <p>Click or drag file here</p>
                                    <div class="upload-hint">JPG, PNG, PDF (max 5MB)</div>
                                    <div class="file-name" id="icFileName">No file selected</div>
                                    <input type="file" id="icPhoto" name="icPhoto" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" required>
                                </div>
                                
                                <div class="file-upload-box" id="licenseUploadBox">
                                    <div class="file-icon">🚗</div>
                                    <p><strong>License Photo</strong></p>
                                    <p>Click or drag file here</p>
                                    <div class="upload-hint">JPG, PNG, PDF (max 5MB)</div>
                                    <div class="file-name" id="licenseFileName">No file selected</div>
                                    <input type="file" id="licensePhoto" name="licensePhoto" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" required>
                                </div>
                            </div>
                            
                            <div class="error-message" id="fileError">Please upload both IC and License photos</div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="resetBtn">
                                <span>Reset Form</span>
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span>Submit Registration</span>
                            </button>
                        </div>
                        
                        <!-- Form Notes -->
                        <div class="form-note">
                            <p><strong>Important Information:</strong></p>
                            <ul>
                                <li>Your registration will be reviewed within 3-5 business days</li>
                                <li>You will receive a confirmation email once your application is approved</li>
                                <li>As an EcoCommute driver, you agree to follow our community guidelines</li>
                                <li>All information provided will be kept confidential and secure</li>
                                <li>Your personal information (Name, Email, Phone) is linked from your user account</li>
                                <li><strong>Note:</strong> Each user can only register as a driver once.</li>
                            </ul>
                        </div>
                        
                        <!-- Hidden field for passing user ID -->
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Form validation function
        function validateForm() {
            let isValid = true;
            let errors = [];
            
            // Reset all error states
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('input').forEach(input => {
                if (!input.classList.contains('readonly-input')) {
                    input.classList.remove('error', 'success');
                }
            });
            
            // Validate vehicle model
            const vehicleModel = document.getElementById('vehicleModel');
            if (!vehicleModel.value.trim()) {
                document.getElementById('vehicleError').style.display = 'block';
                vehicleModel.classList.add('error');
                isValid = false;
                errors.push('Vehicle model is required');
            } else {
                vehicleModel.classList.add('success');
            }
            
            // Validate car plate
            const plate = document.getElementById('carPlate');
            if (!plate.value.trim()) {
                document.getElementById('plateError').style.display = 'block';
                plate.classList.add('error');
                isValid = false;
                errors.push('Car plate number is required');
            } else {
                plate.classList.add('success');
            }
            
            // Validate seats available
            const seats = document.getElementById('seatsAvailable');
            if (seats.value <= 0 || seats.value > 10) {
                document.getElementById('seatsError').style.display = 'block';
                seats.classList.add('error');
                isValid = false;
                errors.push('Seats available must be between 1 and 10');
            } else {
                seats.classList.add('success');
            }
            
            // Validate license number
            const license = document.getElementById('licenseNumber');
            if (!license.value.trim()) {
                document.getElementById('licenseError').style.display = 'block';
                license.classList.add('error');
                isValid = false;
                errors.push('License number is required');
            } else {
                license.classList.add('success');
            }
            
            // Validate file uploads
            const icFile = document.getElementById('icPhoto').files.length;
            const licenseFile = document.getElementById('licensePhoto').files.length;
            
            if (icFile === 0 || licenseFile === 0) {
                document.getElementById('fileError').style.display = 'block';
                isValid = false;
                errors.push('Please upload both IC and License photos');
            }
            
            return {isValid, errors};
        }
        
        // File upload handling
        function setupFileUpload() {
            const icUploadBox = document.getElementById('icUploadBox');
            const licenseUploadBox = document.getElementById('licenseUploadBox');
            const icFileInput = document.getElementById('icPhoto');
            const licenseFileInput = document.getElementById('licensePhoto');
            const icFileName = document.getElementById('icFileName');
            const licenseFileName = document.getElementById('licenseFileName');
            
            // IC upload handling
            icUploadBox.addEventListener('click', () => {
                icFileInput.click();
            });
            
            icFileInput.addEventListener('change', () => {
                if (icFileInput.files.length > 0) {
                    icFileName.textContent = icFileInput.files[0].name;
                    icUploadBox.style.borderColor = 'var(--light-green)';
                    icUploadBox.style.backgroundColor = '#f1f8e9';
                } else {
                    icFileName.textContent = 'No file selected';
                    icUploadBox.style.borderColor = '#c8e6c9';
                    icUploadBox.style.backgroundColor = '#f8fff8';
                }
            });
            
            // License upload handling
            licenseUploadBox.addEventListener('click', () => {
                licenseFileInput.click();
            });
            
            licenseFileInput.addEventListener('change', () => {
                if (licenseFileInput.files.length > 0) {
                    licenseFileName.textContent = licenseFileInput.files[0].name;
                    licenseUploadBox.style.borderColor = 'var(--light-green)';
                    licenseUploadBox.style.backgroundColor = '#f1f8e9';
                } else {
                    licenseFileName.textContent = 'No file selected';
                    licenseUploadBox.style.borderColor = '#c8e6c9';
                    licenseUploadBox.style.backgroundColor = '#f8fff8';
                }
            });
            
            // Drag and drop functionality
            [icUploadBox, licenseUploadBox].forEach(box => {
                box.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    box.classList.add('drag-over');
                });
                
                box.addEventListener('dragleave', () => {
                    box.classList.remove('drag-over');
                });
                
                box.addEventListener('drop', (e) => {
                    e.preventDefault();
                    box.classList.remove('drag-over');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        if (box === icUploadBox) {
                            icFileInput.files = files;
                            icFileName.textContent = files[0].name;
                            box.style.borderColor = 'var(--light-green)';
                            box.style.backgroundColor = '#f1f8e9';
                        } else {
                            licenseFileInput.files = files;
                            licenseFileName.textContent = files[0].name;
                            box.style.borderColor = 'var(--light-green)';
                            box.style.backgroundColor = '#f1f8e9';
                        }
                    }
                });
            });
        }
        
        // Form submission handling
        const form = document.getElementById('driverRegistrationForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const validationResult = validateForm();
                
                if (validationResult.isValid) {
                    // Show submitting state
                    const submitBtn = document.getElementById('submitBtn');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<span>Submitting...</span>';
                    submitBtn.classList.add('btn-disabled');
                    
                    // Submit form
                    this.submit();
                } else {
                    // Show error messages
                    alert('Please correct the errors in the form:\n\n' + validationResult.errors.join('\n'));
                }
            });
        }
        
        // Reset form
        const resetBtn = document.getElementById('resetBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                const form = document.getElementById('driverRegistrationForm');
                if (form) {
                    // Reset form but keep readonly fields
                    const allInputs = form.querySelectorAll('input');
                    allInputs.forEach(input => {
                        if (!input.classList.contains('readonly-input') && input.type !== 'hidden') {
                            if (input.type === 'file') {
                                input.value = '';
                            } else {
                                input.value = '';
                            }
                        }
                    });
                    
                    document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('input').forEach(input => {
                        if (!input.classList.contains('readonly-input')) {
                            input.classList.remove('error', 'success');
                        }
                    });
                    
                    document.getElementById('icFileName').textContent = 'No file selected';
                    document.getElementById('licenseFileName').textContent = 'No file selected';
                    
                    // Reset file upload box styles
                    document.getElementById('icUploadBox').style.borderColor = '#c8e6c9';
                    document.getElementById('icUploadBox').style.backgroundColor = '#f8fff8';
                    document.getElementById('licenseUploadBox').style.borderColor = '#c8e6c9';
                    document.getElementById('licenseUploadBox').style.backgroundColor = '#f8fff8';
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('icUploadBox')) {
                setupFileUpload();
            }
            
            // Sidebar interaction
            document.querySelectorAll('.nav-btn').forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all links
                    document.querySelectorAll('.nav-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to current link
                    this.classList.add('active');
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+S to submit form
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.click();
                    }
                }
                // Esc key to reset form
                if (e.key === 'Escape') {
                    const resetBtn = document.getElementById('resetBtn');
                    if (resetBtn) {
                        resetBtn.click();
                    }
                }
                // Ctrl+H to go to homepage
                if (e.ctrlKey && e.key === 'h') {
                    e.preventDefault();
                    window.location.href = 'homepage.php';
                }
            });
        });
    </script>
</body>
</html>