<?php
    session_start();

    
    $host = 'localhost';
    $dbname = 'wdd';        
    $username = 'root';
    $password = '';

    

    
    $error = '';
    $success = '';
    $tpnumber = $fullname = $username_input = $email = $phone = $gender = '';

    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/ASS-WDD/uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    try {
        // 连接数据库 - 使用简单的字符集设置
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        
        
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $tpnumber = strtoupper(trim($_POST['tpnumber'] ?? ''));
            $fullname = trim($_POST['fullname'] ?? '');
            $username_input = trim($_POST['username'] ?? '');
            $password_input = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $gender = $_POST['gender'] ?? '';
            
           
            $errors = [];
            
            
            if (empty($tpnumber)) $errors[] = 'TP Number is required';
            if (empty($fullname)) $errors[] = 'Full name is required';
            if (empty($username_input)) $errors[] = 'Username is required';
            if (empty($password_input)) $errors[] = 'Password is required';
            if (empty($email)) $errors[] = 'Email is required';
            if (empty($phone)) $errors[] = 'Phone number is required';
            if (empty($gender)) $errors[] = 'Gender is required';
            
            
            if (!empty($tpnumber) && !preg_match('/^TP\d+$/i', $tpnumber)) {
                $errors[] = 'TP Number must start with TP followed by numbers (e.g., TP123456)';
            }
            
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            
            if (!empty($password_input) && strlen($password_input) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            
            
            $photo_url = null;
            $file_uploaded = false;
            
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    $file_type = $_FILES['photo']['type'];
                    $file_size = $_FILES['photo']['size'];
                    $file_name = $_FILES['photo']['name'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        $errors[] = 'Only JPG, PNG, and GIF images are allowed';
                    } elseif ($file_size > 5 * 1024 * 1024) {
                        $errors[] = 'Image size must be less than 5MB';
                    } else {
                        
                        $file_name = time() . "_" . basename($_FILES['photo']['name']);
                        $target_file = $upload_dir . $file_name;
                        $db_file_path = "uploads/" . $file_name; 
                        
                        
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                            $photo_url = $db_file_path;
                            $file_uploaded = true;
                        } else {
                            $errors[] = 'Failed to upload image. Please try again.';
                        }
                    }
                } else {
                    $errors[] = 'File upload error. Please try again.';
                }
            } else {
                $errors[] = 'Profile photo is required';
            }
            
        
            if (empty($errors) && $file_uploaded) {
                try {
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_acc WHERE username = ?");
                    $stmt->execute([$username_input]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Username already exists';
                    }
                    
                  
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_acc WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Email already exists';
                    }
                    
                 
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_acc WHERE apu_id = ?");
                    $stmt->execute([$tpnumber]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'TP Number already registered';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error while checking existing records: ' . $e->getMessage();
                }
            }
            
           
            if (empty($errors)) {
                try {
                   
                    $plain_password = $password_input;
                    
                    // 准备SQL插入语句 - 根据 wdd.user_acc 表结构调整
                    $sql = "INSERT INTO user_acc (apu_id, name, username, password, email, phone_number, gender, photo_url, current_point, summary_point, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    
                    // 设置默认值
                    $current_point = 0;
                    $summary_point = 0;
                    $role = 'User';
                    
                    // 执行插入
                    if ($stmt->execute([
                        $tpnumber,
                        $fullname,
                        $username_input,
                        $plain_password,
                        $email,
                        $phone,
                        $gender,
                        $photo_url,
                        $current_point,
                        $summary_point,
                        $role
                    ])) {
                        $user_id = $conn->lastInsertId();
                        
                       
                        $_SESSION['user_id'] = $conn->lastInsertId();
                        $_SESSION['name'] = $fullname;
                        $_SESSION['username'] = $username_input;
                        $_SESSION['role'] = $role;
                        $_SESSION['apu_id'] = $tpnumber;
                        $_SESSION['email'] = $email;
                        $_SESSION['phone_number'] = $phone;
                        $_SESSION['gender'] = $gender;
                        $_SESSION['photo_url'] = $photo_url;
                        $_SESSION['summary_point'] = $summary_point;
                        $_SESSION['current_point'] = $current_point;

                        
                        header('Location: homepage.php');
                        exit();

                    } else {
                        $errors[] = 'Registration failed. Please try again.';
                    }
                    
                } catch(PDOException $e) {
                    // 检查是否是唯一约束错误
                    if ($e->getCode() == '23000') {
                        $errors[] = 'Username, email or TP number already exists.';
                    } else {
                        $errors[] = 'Database error during registration: ' . $e->getMessage();
                    }
                    error_log("PDO Exception: " . $e->getMessage());
                }
            }
            
            // 如果有错误，显示错误
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            }
        }
        
    } catch(PDOException $e) {
        $error = 'Database connection failed. Please check your database settings: ' . $e->getMessage();
        error_log("Database Connection Error: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | EcoCommute</title>
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
            --primary-color: #2e7d32;
            --primary-dark: #1b5e20;
            --secondary-color: #4caf50;
            --accent-color: #81c784;
            --light-color: #f1f8e9;
            --dark-color: #1b5e20;
            --gray-color: #666;
            --error-color: #d32f2f;
            --success-color: #388e3c;
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
            background: linear-gradient(135deg, #2e7d32, #4caf50);
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            bottom: 10%;
            right: 5%;
            animation-delay: 5s;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #14532d, #81c784);
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

        
        .message-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-error {
            background-color: #ffebee;
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .alert-success {
            background-color: #e8f5e9;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
        }

        /* 注册容器 */
        .signup-container {
            width: 1000px;
            max-width: 100%;
            min-height: 650px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 50px 60px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        /* 装饰边框效果 */
        .signup-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .header-content h2 {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .header-content p {
            font-size: 16px;
            color: var(--gray-color);
            line-height: 1.5;
        }

        .login-link {
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
            background: rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }

        .login-link:hover {
            background: rgba(46, 125, 50, 0.15);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
        }

        .login-link i {
            font-size: 14px;
        }

        /* 表单区域 */
        .form-section {
            margin-top: 20px;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .error-msg {
            color: var(--error-color);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background-color: white;
            color: var(--dark-color);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
            font-size: 18px;
            z-index: 2;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
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
            background: rgba(46, 125, 50, 0.1);
            border-radius: 50%;
        }

        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 8px;
            background-color: #e2e8f0;
            overflow: hidden;
            position: relative;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: var(--transition);
            border-radius: 5px;
        }

        .strength-text {
            font-size: 12px;
            color: var(--gray-color);
            margin-top: 5px;
            text-align: right;
        }

        /* 双列布局 */
        .input-row {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }

        .input-row .input-group {
            flex: 1;
            margin-bottom: 0;
        }

        /* 文件上传样式 */
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-btn {
            padding: 16px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: var(--gray-color);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .file-upload-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-name {
            font-size: 14px;
            color: var(--gray-color);
            margin-top: 8px;
            display: none;
        }

        
        .signup-btn {
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
            box-shadow: 0 12px 25px rgba(46, 125, 50, 0.35);
            margin-top: 25px;
            font-family: 'Inter', sans-serif;
        }

        .signup-btn:hover:not(:disabled) {
            transform: translateY(-4px);
            box-shadow: 0 18px 35px rgba(46, 125, 50, 0.45);
        }

        .signup-btn:active:not(:disabled) {
            transform: translateY(-1px);
        }

        .signup-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .signup-btn i {
            font-size: 18px;
            transition: var(--transition);
        }

        .signup-btn:hover i:not(:disabled) {
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

       
        @keyframes success-check {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        
        .terms {
            font-size: 13px;
            color: var(--gray-color);
            text-align: center;
            margin-top: 25px;
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

        
        @media (max-width: 1100px) {
            .signup-container {
                width: 90%;
                padding: 40px 35px;
            }
            
            .header-section {
                flex-direction: column;
                gap: 20px;
            }
            
            .login-link {
                align-self: flex-start;
            }
        }

        @media (max-width: 768px) {
            .signup-container {
                padding: 35px 25px;
            }
            
            .input-row {
                flex-direction: column;
                gap: 20px;
            }
            
            .header-content h2 {
                font-size: 30px;
            }
        }

        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
            
            .header-content h2 {
                font-size: 26px;
            }
            
            .form-input {
                padding: 14px 20px 14px 45px;
            }
            
            .input-icon {
                left: 15px;
            }
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

    <!-- 消息提示容器 -->
    <div class="message-container">
        <?php if ($error): ?>
        <div class="alert alert-error">
            <div>
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <div>
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="signup-container">
        <div class="header-section">
            <div class="header-content">
                <h2>
                    <i class="fas fa-user-plus"></i> Create Account
                </h2>
                <p>Join EcoCommute and start your sustainable journey today!</p>
            </div>
            
            <a href="login.php" class="login-link">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </a>
        </div>
        
        <div class="form-section">
            <form id="signupForm" method="POST" action="" enctype="multipart/form-data" onsubmit="submitForm()">
                <div class="input-group">
                    <label>
                        TP Number
                        <span class="error-msg" id="tp-error"></span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" id="tpnumber" name="tpnumber" class="form-input" placeholder="Enter TP Number (e.g., TP123456)" 
                               value="<?php echo htmlspecialchars($tpnumber); ?>" required>
                    </div>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label>
                            Full Name
                            <span class="error-msg" id="name-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="fullname" name="fullname" class="form-input" placeholder="Enter full name" 
                                   value="<?php echo htmlspecialchars($fullname); ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>
                            Username
                            <span class="error-msg" id="username-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Choose a username" 
                                   value="<?php echo htmlspecialchars($username_input); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label>
                            Password
                            <span class="error-msg" id="password-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Password strength: None</div>
                    </div>
                    
                    <div class="input-group">
                        <label>
                            Email Address
                            <span class="error-msg" id="email-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label>
                            Phone Number
                            <span class="error-msg" id="phone-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="text" id="phone" name="phone" class="form-input" placeholder="Enter phone number" 
                                   value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>
                            Gender
                            <span class="error-msg" id="gender-error"></span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-venus-mars input-icon"></i>
                            <select id="gender" name="gender" class="form-input" required>
                                <option value="">Select gender</option>
                                <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($gender == 'other') ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer-not-to-say" <?php echo ($gender == 'prefer-not-to-say') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="input-group">
                    <label>
                        Profile Photo
                        <span class="error-msg" id="photo-error"></span>
                    </label>
                    <div class="file-upload-wrapper">
                        <button type="button" class="file-upload-btn" id="fileUploadBtn">
                            <i class="fas fa-upload"></i> Choose a photo
                        </button>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                    </div>
                    <div class="file-name" id="fileName"></div>
                </div>
                
                <button type="submit" class="signup-btn" id="signupButton">
                    <span id="buttonText">Create Account</span>
                    <i class="fas fa-user-plus"></i>
                    <div class="loader" id="buttonLoader"></div>
                </button>
            </form>
            
            <p class="terms">
                By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                You also agree to participate in EcoCommute's sustainable transportation initiatives.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const signupForm = document.getElementById('signupForm');
            const tpInput = document.getElementById('tpnumber');
            const fullnameInput = document.getElementById('fullname');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const genderSelect = document.getElementById('gender');
            const photoInput = document.getElementById('photo');
            const togglePassword = document.getElementById('togglePassword');
            const signupButton = document.getElementById('signupButton');
            const buttonText = document.getElementById('buttonText');
            const buttonLoader = document.getElementById('buttonLoader');
            const fileUploadBtn = document.getElementById('fileUploadBtn');
            const fileName = document.getElementById('fileName');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            
            const tpError = document.getElementById('tp-error');
            const nameError = document.getElementById('name-error');
            const usernameError = document.getElementById('username-error');
            const passwordError = document.getElementById('password-error');
            const emailError = document.getElementById('email-error');
            const phoneError = document.getElementById('phone-error');
            const genderError = document.getElementById('gender-error');
            const photoError = document.getElementById('photo-error');
            
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            
            photoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    fileName.textContent = `Selected: ${file.name}`;
                    fileName.style.display = 'block';
                    fileUploadBtn.innerHTML = `<i class="fas fa-check"></i> ${file.name}`;
                    
                   
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!validTypes.includes(file.type)) {
                        photoError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please upload an image (JPEG, PNG, GIF)';
                        photoError.style.display = 'block';
                        this.value = '';
                        fileName.style.display = 'none';
                        fileUploadBtn.innerHTML = '<i class="fas fa-upload"></i> Choose a photo';
                    } else if (file.size > maxSize) {
                        photoError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Image size should be less than 5MB';
                        photoError.style.display = 'block';
                        this.value = '';
                        fileName.style.display = 'none';
                        fileUploadBtn.innerHTML = '<i class="fas fa-upload"></i> Choose a photo';
                    } else {
                        photoError.innerHTML = '';
                        photoError.style.display = 'none';
                    }
                }
            });
            
            // 密码强度检查
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
            
            function checkPasswordStrength(password) {
                let strength = 0;
                let text = '';
                let color = '';
                
                // 长度检查
                if (password.length >= 8) strength += 25;
                
                // 小写字母检查
                if (/[a-z]/.test(password)) strength += 25;
                
                // 大写字母检查
                if (/[A-Z]/.test(password)) strength += 25;
                
                // 数字和特殊字符检查
                if (/[0-9]/.test(password)) strength += 15;
                if (/[^A-Za-z0-9]/.test(password)) strength += 10;
                
                // 设置文本和颜色
                if (strength === 0) {
                    text = 'None';
                    color = '#e2e8f0';
                } else if (strength <= 25) {
                    text = 'Weak';
                    color = '#ef4444';
                } else if (strength <= 50) {
                    text = 'Fair';
                    color = '#f59e0b';
                } else if (strength <= 75) {
                    text = 'Good';
                    color = '#4caf50';
                } else {
                    text = 'Strong';
                    color = '#2e7d32';
                }
                
                strengthBar.style.width = `${strength}%`;
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = `Password strength: ${text}`;
                strengthText.style.color = color;
            }
            
            // TP号码格式化
            tpInput.addEventListener('input', function() {
                let val = this.value.toUpperCase();
                
                // 移除所有非字母数字字符
                val = val.replace(/[^A-Z0-9]/g, '');
                
                // 确保以TP开头
                if (!val.startsWith("TP")) {
                    val = "TP" + val.replace(/[^0-9]/g, "");
                } else {
                    val = "TP" + val.substring(2).replace(/[^0-9]/g, "");
                }
                
                this.value = val;
            });
            
            // 电话号码验证
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, "");
            });
            
            // 自动隐藏消息提示
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        });
        
        // 表单提交函数
        function submitForm() {
            // 显示加载状态
            document.getElementById('buttonText').style.display = 'none';
            document.getElementById('buttonLoader').style.display = 'block';
            document.getElementById('signupButton').disabled = true;
            
            // 允许表单正常提交
            return true;
        }
    </script>
</body>
</html>