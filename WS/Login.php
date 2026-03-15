<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit();
}

 
$error_message = '';
$success_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $host = 'localhost';
    $dbname = 'wdd';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        
        if (isset($_POST['forgot_password_action'])) {
            if ($_POST['forgot_password_action'] === 'verify_email') {
                
                $email = trim($_POST['email'] ?? '');
                
                if (empty($email)) {
                    $error_message = 'Please enter your email address.';
                } else {
                
                    $query = "SELECT user_id, email FROM user_acc WHERE email = :email LIMIT 1";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        $_SESSION['reset_email'] = $user['email'];
                        $_SESSION['reset_user_id'] = $user['user_id'];
                        $_SESSION['reset_step'] = 2;
                        $success_message = 'Email verified. Please enter your new password.';
                    } else {
                        $error_message = 'Email address not found in our system.';
                    }
                }
            } 
            elseif ($_POST['forgot_password_action'] === 'reset_password') {
                
                if (!isset($_SESSION['reset_email'], $_SESSION['reset_user_id'])) {
                    $error_message = 'Please verify your email first.';
                } else {
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    if (empty($new_password) || empty($confirm_password)) {
                        $error_message = 'Please enter and confirm your new password.';
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = 'Passwords do not match.';
                    } else {
                        
                        $update_query = "UPDATE user_acc SET password = :password WHERE user_id = :user_id";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bindParam(':password', $new_password);
                        $update_stmt->bindParam(':user_id', $_SESSION['reset_user_id']);
                        
                        if ($update_stmt->execute()) {
                            $success_message = 'Password has been reset successfully! You can now login with your new password.';
                            
                            unset($_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_step']);
                        } else {
                            $error_message = 'Failed to reset password. Please try again.';
                        }
                    }
                }
            }
        } else {
            
            $user_input = trim($_POST['username'] ?? '');
            $password_input = $_POST['password'] ?? '';
            $remember_me = isset($_POST['remember']) ? true : false;
            
            if (empty($user_input) || empty($password_input)) {
                $error_message = 'Please enter both username and password.';
            } else {
                
                $query = "SELECT user_id, apu_id, name, username, password, email, phone_number, 
                                 gender, photo_url, summary_point, current_point, role 
                          FROM user_acc 
                          WHERE username = :username OR email = :username 
                          LIMIT 1";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $user_input);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    
                    if ($user['password'] === $password_input) {
                        
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['apu_id'] = $user['apu_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['phone_number'] = $user['phone_number'];
                        $_SESSION['gender'] = $user['gender'];
                        $_SESSION['photo_url'] = $user['photo_url'];
                        $_SESSION['summary_point'] = $user['summary_point'];
                        $_SESSION['current_point'] = $user['current_point'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        
                      
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            $cookie_data = [
                                'user_id' => $user['user_id'],
                                'token' => $token,
                                'expires' => time() + (86400 * 30)
                            ];
                            setcookie('remember_me', json_encode($cookie_data), time() + (86400 * 30), "/", "", false, true);
                        }
                        
                        
                        header('Location: homepage.php');
                        exit();
                    } else {
                        $error_message = 'Invalid username or password.';
                    }
                } else {
                    $error_message = 'Invalid username or password.';
                }
            }
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error_message = 'An error occurred. Please try again later.';
    }
}


if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    try {
        $cookie_data = json_decode($_COOKIE['remember_me'], true);
        
        if ($cookie_data && isset($cookie_data['user_id'], $cookie_data['token'], $cookie_data['expires'])) {
            if ($cookie_data['expires'] > time()) {
                $host = 'localhost';
                $dbname = 'wdd';
                $username = 'root';
                $password = '';
                
                $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $query = "SELECT user_id, apu_id, name, username, email, phone_number, 
                                 gender, photo_url, summary_point, current_point, role 
                          FROM user_acc 
                          WHERE user_id = :user_id 
                          LIMIT 1";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $cookie_data['user_id']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['apu_id'] = $user['apu_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone_number'] = $user['phone_number'];
                    $_SESSION['gender'] = $user['gender'];
                    $_SESSION['photo_url'] = $user['photo_url'];
                    $_SESSION['summary_point'] = $user['summary_point'];
                    $_SESSION['current_point'] = $user['current_point'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    header('Location: homepage.php');
                    exit();
                }
            } else {
                setcookie('remember_me', '', time() - 3600, "/");
            }
        }
    } catch(PDOException $e) {
        setcookie('remember_me', '', time() - 3600, "/");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Login | Aurora</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-color: #10b981;
        --primary-dark: #059669;
        --secondary-color: #34d399;
        --accent-color: #f59e0b;
        --light-color: #f8fafc;
        --dark-color: #1e293b;
        --gray-color: #64748b;
        --success-color: #10b981;
        --error-color: #ef4444;
        --glass-bg: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.2);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.15);
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        color: var(--dark-color);
        background: linear-gradient(135deg, #0a2e1d 0%, #14532d 50%, #1e7c4d 100%);
        position: relative;
        overflow-x: hidden;
        padding: 20px;
    }

    .background-animation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        overflow: hidden;
    }

    .shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.4;
        animation: float 15s infinite ease-in-out;
    }

    .shape-1 {
        width: 500px;
        height: 500px;
        background: linear-gradient(135deg, #10b981, #34d399);
        top: 10%;
        left: 5%;
        animation-delay: 0s;
    }

    .shape-2 {
        width: 400px;
        height: 400px;
        background: linear-gradient(135deg, #059669, #10b981);
        bottom: 10%;
        right: 5%;
        animation-delay: 5s;
    }

    .shape-3 {
        width: 300px;
        height: 300px;
        background: linear-gradient(135deg, #047857, #0ea5e9);
        top: 50%;
        right: 15%;
        animation-delay: 10s;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0) rotate(0deg);
        }
        33% {
            transform: translateY(-30px) rotate(120deg);
        }
        66% {
            transform: translateY(30px) rotate(240deg);
        }
    }

    .container {
        width: 1200px;
        max-width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 60px;
        padding: 20px;
    }

    .left-content {
        max-width: 550px;
        position: relative;
        z-index: 1;
        padding: 40px;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 40px;
    }

    .logo-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 26px;
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    }

    .logo-text {
        font-size: 32px;
        font-weight: 800;
        color: white;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .welcome-title {
        font-size: 52px;
        font-weight: 800;
        line-height: 1.15;
        color: white;
        margin-bottom: 25px;
        background: linear-gradient(135deg, #ffffff 0%, #d1fae5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .welcome-subtitle {
        font-size: 19px;
        line-height: 1.7;
        color: #e2f7ed;
        margin-bottom: 50px;
        font-weight: 400;
    }

    .features {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-top: 40px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 18px;
        color: #e2f7ed;
        font-size: 17px;
        transition: var(--transition);
        padding: 12px 16px;
        border-radius: 12px;
    }

    .feature-item:hover {
        color: white;
        transform: translateX(8px);
        background: rgba(255, 255, 255, 0.1);
    }

    .feature-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        transition: var(--transition);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .feature-item:hover .feature-icon {
        background: rgba(16, 185, 129, 0.3);
        transform: scale(1.1);
    }

    .feature-text {
        flex: 1;
        line-height: 1.5;
    }

    .feature-text strong {
        color: white;
        font-weight: 600;
    }

    .login-box {
        width: 480px;
        max-width: 100%;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 28px;
        padding: 50px 45px;
        box-shadow: var(--shadow-xl);
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .login-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), #0ea5e9);
    }

    .login-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .login-title {
        font-size: 34px;
        font-weight: 700;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .login-subtitle {
        color: var(--gray-color);
        font-size: 16px;
        margin-top: 8px;
        margin-bottom: 35px;
        line-height: 1.5;
    }

    .signup-link {
        font-size: 15px;
        font-weight: 600;
        color: var(--primary-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        padding: 10px 18px;
        border-radius: 10px;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .signup-link:hover {
        background: rgba(16, 185, 129, 0.15);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
    }

    .signup-link i {
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 28px;
        position: relative;
    }

    .form-label {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
    }

    .form-input-wrapper {
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 18px 20px 18px 55px;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 16px;
        font-family: 'Inter', sans-serif;
        transition: var(--transition);
        background-color: white;
        color: var(--dark-color);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-color);
        font-size: 18px;
        z-index: 2;
    }

    .toggle-password {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--gray-color);
        cursor: pointer;
        font-size: 18px;
        transition: var(--transition);
        padding: 5px;
    }

    .toggle-password:hover {
        color: var(--primary-color);
        background: rgba(16, 185, 129, 0.1);
        border-radius: 50%;
    }

    .error-msg {
        color: #ef4444;
        font-size: 14px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 22px;
    }

    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }

    .custom-checkbox {
        width: 22px;
        height: 22px;
        border: 2px solid #cbd5e1;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        background: white;
    }

    .checkbox-input:checked + .custom-checkbox {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .checkbox-input:checked + .custom-checkbox::after {
        content: '✓';
        color: white;
        font-size: 14px;
        font-weight: bold;
    }

    .checkbox-input {
        display: none;
    }

    .checkbox-label {
        font-size: 15px;
        color: var(--dark-color);
        user-select: none;
        font-weight: 500;
    }

    .forgot-link {
        font-size: 15px;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 8px;
    }

    .forgot-link:hover {
        color: var(--primary-dark);
        background: rgba(16, 185, 129, 0.1);
        text-decoration: none;
    }

    .login-btn {
        width: 100%;
        padding: 20px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        border-radius: 14px;
        color: white;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 25px rgba(16, 185, 129, 0.35);
        margin-top: 25px;
    }

    .login-btn:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 35px rgba(16, 185, 129, 0.45);
    }

    .login-btn:active {
        transform: translateY(-1px);
    }

    .login-btn i {
        font-size: 18px;
        transition: var(--transition);
    }

    .login-btn:hover i {
        transform: translateX(8px);
    }

    .loader {
        display: none;
        width: 22px;
        height: 22px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top: 3px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .security-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #e2e8f0;
        font-size: 14px;
        color: var(--gray-color);
    }

    .security-note i {
        color: var(--success-color);
        font-size: 18px;
    }

    .terms {
        font-size: 13px;
        color: var(--gray-color);
        text-align: center;
        margin-top: 20px;
        line-height: 1.5;
    }

    .terms a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
    }

    .terms a:hover {
        text-decoration: underline;
    }

    .php-error {
        background-color: #fee2e2;
        color: #991b1b;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #ef4444;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .php-error i {
        font-size: 18px;
    }
    
    .php-success {
        background-color: #d1fae5;
        color: #065f46;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #10b981;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .php-success i {
        font-size: 18px;
    }

    @media (max-width: 1100px) {
        .container {
            flex-direction: column;
            gap: 60px;
            padding: 40px 20px;
            width: 100%;
        }
        
        .left-content {
            max-width: 600px;
            width: 100%;
            padding: 35px 30px;
            text-align: center;
        }
        
        .logo {
            justify-content: center;
        }
        
        .welcome-title {
            font-size: 46px;
        }
        
        .features {
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .login-box {
            width: 100%;
            max-width: 500px;
            padding: 40px 35px;
        }
    }

    @media (max-width: 768px) {
        .container {
            gap: 40px;
            padding: 20px 15px;
        }
        
        .welcome-title {
            font-size: 40px;
        }
        
        .welcome-subtitle {
            font-size: 17px;
            margin-bottom: 40px;
        }
        
        .login-box {
            padding: 35px 25px;
        }
        
        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        
        .feature-item {
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        .welcome-title {
            font-size: 34px;
        }
        
        .logo-text {
            font-size: 28px;
        }
        
        .login-title {
            font-size: 28px;
        }
        
        .feature-item {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .feature-text {
            text-align: center;
        }
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal {
        background: white;
        border-radius: 20px;
        padding: 40px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        transform: translateY(-30px);
        transition: var(--transition);
        position: relative;
    }

    .modal-overlay.active .modal {
        transform: translateY(0);
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--gray-color);
        cursor: pointer;
        transition: var(--transition);
    }

    .modal-close:hover {
        color: var(--dark-color);
    }

    .modal-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-subtitle {
        color: var(--gray-color);
        font-size: 15px;
        margin-bottom: 30px;
    }

    .modal-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .modal-btn {
        flex: 1;
        padding: 16px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        border: none;
    }

    .modal-btn.primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .modal-btn.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
    }

    .modal-btn.secondary {
        background: #f1f5f9;
        color: var(--dark-color);
    }

    .modal-btn.secondary:hover {
        background: #e2e8f0;
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e2e8f0;
        color: var(--gray-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 16px;
        transition: var(--transition);
    }

    .step.active .step-number {
        background: var(--primary-color);
        color: white;
    }

    .step-text {
        font-size: 14px;
        color: var(--gray-color);
        font-weight: 500;
    }

    .step.active .step-text {
        color: var(--dark-color);
        font-weight: 600;
    }

    .step-line {
        flex: 1;
        height: 2px;
        background: #e2e8f0;
        margin-top: 17px;
    }

    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
    }
</style>
</head>
<body>
    <!-- 动态背景 -->
    <div class="background-animation">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="container">
        <!-- 左侧内容 -->
        <div class="left-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="logo-text">GOby</div>
            </div>
            
            <h1 class="welcome-title">Welcome Back</h1>
            <p class="welcome-subtitle">Login to follow up your dashboard and access all features. Experience the next generation of secure authentication.</p>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Secure authentication</strong> with biometric options
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Fast and reliable</strong> with 99.9% uptime guarantee
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Exclusive access</strong> to premium features
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 登录框 -->
        <div class="login-box">
            <div class="login-header">
                <h2 class="login-title">
                    <i class="fas fa-lock"></i> Login
                </h2>
                <a href="sign_up.php" class="signup-link">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            </div>
            
            <p class="login-subtitle">Enter your credentials to access your account</p>
            
            <?php if ($error_message): ?>
            <div class="php-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="php-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- 如果是忘记密码的第二步，显示重置密码表单 -->
            <?php if (isset($_SESSION['reset_step']) && $_SESSION['reset_step'] == 2): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="forgot_password_action" value="reset_password">
                
                <div class="form-group">
                    <label class="form-label">
                        New Password
                        <span class="error-msg" id="new-password-error"></span>
                    </label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="new_password" id="newPassword" class="form-input" placeholder="Enter new password">
                        <button type="button" class="toggle-password" id="toggleNewPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Confirm New Password
                        <span class="error-msg" id="confirm-password-error"></span>
                    </label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-input" placeholder="Confirm new password">
                        <button type="button" class="toggle-password" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <a href="login.php" class="forgot-link">Back to Login</a>
                </div>
                
                <button type="submit" class="login-btn" id="resetPasswordBtn">
                    <span id="resetButtonText">Reset Password</span>
                    <i class="fas fa-key"></i>
                </button>
            </form>
            <?php else: ?>
            <!-- 正常登录表单 -->
            <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label class="form-label">
                        Username or Email
                        <span class="error-msg" id="username-error"></span>
                    </label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Enter username or email" autocomplete="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Password
                        <span class="error-msg" id="password-error"></span>
                    </label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" autocomplete="current-password">
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="remember" name="remember" class="checkbox-input" 
                               <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        <span class="custom-checkbox"></span>
                        <span class="checkbox-label">Remember me</span>
                    </label>
                    
                    <a href="#" class="forgot-link" id="forgotPasswordBtn">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn" id="loginButton">
                    <span id="buttonText">Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            <?php endif; ?>
            
            <div class="security-note">
                <i class="fas fa-shield-check"></i>
                <span>Your login is secured with 256-bit encryption</span>
            </div>
            
            <p class="terms">
                By logging in, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </p>
        </div>
    </div>

    <!-- 忘记密码弹窗（只用于第一步） -->
    <?php if (!isset($_SESSION['reset_step'])): ?>
    <div class="modal-overlay" id="forgotPasswordModal">
        <div class="modal">
            <button class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-title">
                <i class="fas fa-key"></i> Reset Password
            </h2>
            
            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <div class="step-text">Verify Email</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <div class="step-text">Reset Password</div>
                </div>
            </div>
            
            <!-- 步骤1: 验证邮箱 -->
            <div class="step-content active" id="step1Content">
                <p class="modal-subtitle">Enter your registered email address to reset your password.</p>
                
                <div id="emailMessage"></div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="verifyEmailForm">
                    <input type="hidden" name="forgot_password_action" value="verify_email">
                    
                    <div class="form-group">
                        <label class="form-label">
                            Email Address
                            <span class="error-msg" id="email-error"></span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" id="resetEmail" class="form-input" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn primary" id="verifyEmailBtn">
                            <span id="verifyButtonText">Verify Email</span>
                            <div class="loader" id="verifyButtonLoader" style="display: none;"></div>
                        </button>
                        <button type="button" class="modal-btn secondary" id="cancelReset">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

   <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const loginButton = document.getElementById('loginButton');
            const usernameError = document.getElementById('username-error');
            const passwordError = document.getElementById('password-error');
            
            // 忘记密码相关元素
            const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');
            const forgotPasswordModal = document.getElementById('forgotPasswordModal');
            const closeModal = document.getElementById('closeModal');
            const cancelReset = document.getElementById('cancelReset');
            
            // 验证邮箱表单元素
            const verifyEmailForm = document.getElementById('verifyEmailForm');
            const verifyEmailBtn = document.getElementById('verifyEmailBtn');
            const verifyButtonText = document.getElementById('verifyButtonText');
            const verifyButtonLoader = document.getElementById('verifyButtonLoader');
            const resetEmailInput = document.getElementById('resetEmail');
            
            // 重置密码表单元素
            const newPasswordInput = document.getElementById('newPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const toggleNewPassword = document.getElementById('toggleNewPassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const resetPasswordBtn = document.getElementById('resetPasswordBtn'); 
            
            // 切换密码可见性
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // 切换新密码可见性
            if (toggleNewPassword) {
                toggleNewPassword.addEventListener('click', function() {
                    const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    newPasswordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // 切换确认密码可见性
            if (toggleConfirmPassword) {
                toggleConfirmPassword.addEventListener('click', function() {
                    const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // 打开忘记密码弹窗
            if (forgotPasswordBtn) {
                forgotPasswordBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    forgotPasswordModal.classList.add('active');
                });
            }
            
            // 关闭忘记密码弹窗
            function closeForgotPasswordModal() {
                if (forgotPasswordModal) {
                    forgotPasswordModal.classList.remove('active');
                }
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', closeForgotPasswordModal);
            }
            
            if (cancelReset) {
                cancelReset.addEventListener('click', closeForgotPasswordModal);
            }
            
            // 点击弹窗外部关闭
            if (forgotPasswordModal) {
                forgotPasswordModal.addEventListener('click', function(e) {
                    if (e.target === forgotPasswordModal) {
                        closeForgotPasswordModal();
                    }
                });
            }
            
            
            function validateLoginForm() {
                let isValid = true;
                
                
                if (usernameError) usernameError.innerHTML = '';
                if (passwordError) passwordError.innerHTML = '';
                
                
                if (!usernameInput.value.trim()) {
                    usernameError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Username or email is required';
                    isValid = false;
                }
                
                
                if (!passwordInput.value) {
                    passwordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password is required';
                    isValid = false;
                }
                
                return isValid;
            }
            
            // 登录表单提交
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    if (!validateLoginForm()) {
                        e.preventDefault();
                        return;
                    }
                });
            }
            
            // 验证邮箱表单提交
            if (verifyEmailForm) {
                verifyEmailForm.addEventListener('submit', function(e) {
                    // 简单的前端验证
                    const email = resetEmailInput.value.trim();
                    const emailError = document.getElementById('email-error');
                    
                    if (!email) {
                        emailError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Email address is required';
                        e.preventDefault();
                        return;
                    }
                    
                    // 显示加载状态
                    verifyButtonText.style.display = 'none';
                    verifyButtonLoader.style.display = 'block';
                    verifyEmailBtn.disabled = true;
                    
                    // 表单会自动提交到服务器
                });
            }
            
           
            if (resetPasswordBtn) {
                resetPasswordBtn.addEventListener('click', function(e) {
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const newPasswordError = document.getElementById('new-password-error');
                    const confirmPasswordError = document.getElementById('confirm-password-error');
                    
                   
                    newPasswordError.innerHTML = '';
                    confirmPasswordError.innerHTML = '';
                    
                    let isValid = true;
                    
                  
                    if (!newPassword) {
                        newPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> New password is required';
                        isValid = false;
                    } else if (newPassword.length < 6) {
                        newPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters';
                        isValid = false;
                    }
                    
                    
                    if (!confirmPassword) {
                        confirmPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please confirm your new password';
                        isValid = false;
                    } else if (newPassword !== confirmPassword) {
                        confirmPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>