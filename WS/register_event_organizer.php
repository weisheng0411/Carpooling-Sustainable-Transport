<?php
// Start session
session_start();

// Database connection configuration
$host = 'localhost';
$dbname = 'wdd';
$username = 'root';
$password = '';

// Variables to store error/success messages
$success_message = '';
$error_message = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Connect to database to get user information
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check if user is already registered as an organizer
    $check_query = "SELECT * FROM organizer WHERE User_ID = :user_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    $is_already_organizer = $check_stmt->rowCount() > 0;
    
    // Get user information
    $user_query = "SELECT * FROM user_acc WHERE user_id = :user_id LIMIT 1";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() > 0) {
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get information from user data
        $tp_number = $user['apu_id'] ?? '';
        $username_input = $user['username'] ?? '';
        $email = $user['email'] ?? '';
        $phone = $user['phone_number'] ?? '';
    }
    
} catch(PDOException $e) {
    // Log error but don't interrupt page
    error_log("Database error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data - only get description field, other fields are read from database
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Basic validation
    $errors = [];
    
    if (empty($description) || strlen($description) < 20) {
        $errors[] = "Description must be at least 20 characters";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Ensure connection is still valid
            if (!isset($conn)) {
                $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
            if ($is_already_organizer) {
                // User is already registered as an organizer
                $error_message = "You have already registered as an event organizer!";
            } else {
                // Insert new organizer record
                $insert_query = "INSERT INTO organizer (User_ID, Description, Status) 
                                VALUES (:user_id, :description, 'Pending')";
                
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':user_id', $user_id);
                $insert_stmt->bindParam(':description', $description);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Registration successful! Your application has been submitted and will be reviewed within 2-3 business days.";
                    
                    // Update status to avoid duplicate registration
                    $is_already_organizer = true;
                    
                    // Clear description field
                    $description = '';
                } else {
                    $error_message = "Registration failed. Please try again.";
                }
            }
            
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        // Display validation errors
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Event Organizer</title>
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
            --error-red: #d93025;
            --success-green: #0f9d58;
            --readonly-bg: #f5f5f5;
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

        /* ========== Sidebar design (identical to homepage) ========== */
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

        /* ========== Main content area (identical to homepage) ========== */
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

        hr {
            border: none;
            height: 1px;
            background: #ddd;
            margin: 20px 0 30px 0;
        }
        
        /* ========== Registration form ========== */
        .form-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .register-form-box {
            background-color: var(--card-bg);
            width: 800px;
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

        .form-section {
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        label {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .required::after {
            content: " *";
            color: var(--error-red);
        }

        .readonly-info {
            font-size: 12px;
            color: var(--text-light);
            font-weight: normal;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #dadce0;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: white;
            width: 100%;
        }

        /* Read-only field styles */
        input[readonly],
        textarea[readonly] {
            background-color: var(--readonly-bg);
            border-color: #e0e0e0;
            color: var(--text-light);
            cursor: not-allowed;
        }

        input[readonly]:focus,
        textarea[readonly]:focus {
            border-color: #e0e0e0;
            box-shadow: none;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus:not([readonly]),
        textarea:focus:not([readonly]) {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        input.error,
        textarea.error {
            border-color: var(--error-red);
        }

        input.success,
        textarea.success {
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
        
        /* PHP message styles */
        .php-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .php-success {
            background-color: #e8f5e9;
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }
        
        .php-error {
            background-color: #ffebee;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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

        /* Responsive design (identical to homepage) */
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
            
            .register-form-box {
                padding: 25px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar design (identical to homepage) -->
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
                    <a href="../LK/JourneyPlanner.php" class="nav-btn">
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
                    <a href="../LK/SmartParkinng.php" class="nav-btn">
                        <span class="icon">🅿️</span>
                        <span>Smart Parking</span>
                    </a>
                    <a href="../LK/Ahm.php" class="nav-btn">
                        <span class="icon">🎯</span>
                        <span>Achievement</span>
                    </a>
                    <a href="../RY/statistics.php" class="nav-btn">
                        <span class="icon">📈</span>
                        <span>Statistic/Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Registration</div>
                    <a href="#" class="nav-btn active">
                        <span class="icon">📝</span>
                        <span>Register Event Organizer</span>
                    </a>
                    <a href="register_driver.php" class="nav-btn">
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
        
        <!-- Main content area -->
        <main class="main">
            <!-- Top bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h2>Register Event Organizer</h2>
                    <p>Become an EcoCommute event organizer and host green events in your community! 🌱</p>
                </div>  
            </div>
           
            <hr>
            
            <!-- Registration form -->
            <div class="form-container">
                <div class="register-form-box">
                    <div class="form-header">
                        <h2>Register Event Organizer</h2>
                        <p class="form-subtitle">Fill out the form below to become an event organizer</p>
                    </div>
                    
                    <!-- Display PHP messages -->
                    <?php if ($success_message): ?>
                    <div class="php-message php-success">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="php-message php-error">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- If user is already an organizer, display message instead of form -->
                    <?php if (isset($is_already_organizer) && $is_already_organizer): ?>
                    <div class="php-message php-success" style="text-align: center; padding: 40px;">
                        <h3>You are already registered as an event organizer!</h3>
                        <p>Your application is being reviewed. You will be notified once it's approved.</p>
                        <p style="margin-top: 20px; font-size: 14px; color: var(--text-light);">
                            Current status: <strong>Pending</strong> (Waiting for admin approval)
                        </p>
                        <a href="homepage.php" class="btn btn-primary" style="margin-top: 20px;">Return to Homepage</a>
                    </div>
                    <?php else: ?>
                    
                    <form id="organizerRegistrationForm" method="POST" action="">
                        
                        <div class="form-section">
                            
                            <div class="form-group">
                                <label for="tpNumber" class="required">
                                    TP Number
                                    <span class="readonly-info">(from your account)</span>
                                </label>
                                <input type="text" id="tpNumber" name="tpNumber" 
                                       value="<?php echo htmlspecialchars($tp_number ?? ''); ?>" 
                                       readonly>
                                <div class="error-message" id="tpError">TP number cannot be empty</div>
                            </div>
                            
                            <!-- Username - auto-filled and unmodifiable -->
                            <div class="form-group">
                                <label for="username" class="required">
                                    Username
                                    <span class="readonly-info">(from your account)</span>
                                </label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username_input ?? ''); ?>" 
                                       readonly>
                                <div class="error-message" id="usernameError">Username cannot be empty</div>
                            </div>
                            
                            <!-- Email - auto-filled and unmodifiable -->
                            <div class="form-group">
                                <label for="email" class="required">
                                    Email Address
                                    <span class="readonly-info">(from your account)</span>
                                </label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                       readonly>
                                <div class="error-message" id="emailError">Please enter a valid email address</div>
                            </div>
                            
                            <!-- Phone - auto-filled and unmodifiable -->
                            <div class="form-group">
                                <label for="phone" class="required">
                                    Phone Number
                                    <span class="readonly-info">(from your account)</span>
                                </label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                       readonly>
                                <div class="error-message" id="phoneError">Please enter a valid phone number (10-11 digits)</div>
                            </div>
                            
                            <!-- Description - requires user input -->
                            <div class="form-group">
                                <label for="description" class="required">Description</label>
                                <textarea id="description" name="description" 
                                          placeholder="Tell us about yourself and your experience with organizing events. Why do you want to become an event organizer?"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                                <div class="error-message" id="descriptionError">Please provide a description (at least 20 characters)</div>
                            </div>
                        </div>
                        
                        <!-- Form actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span>Register as Event Organizer</span>
                            </button>
                        </div>
                        
                        <!-- Form notes -->
                        <div class="form-note">
                            <p><strong>Important Information:</strong></p>
                            <ul>
                                <li><strong>Your personal information (TP Number, Username, Email, Phone) has been automatically filled from your account and cannot be modified.</strong></li>
                                <li>If you need to update your personal information, please visit your Profile page.</li>
                                <li>Your registration will be reviewed within 2-3 business days</li>
                                <li>You will receive a confirmation email once your application is approved</li>
                                <li>As an EcoCommute event organizer, you agree to follow our community guidelines</li>
                                <li>All information provided will be kept confidential and secure</li>
                            </ul>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Form validation function
        function validateForm() {
            let isValid = true;
            let errors = [];
            
            // Reset all error states
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.success-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('input:not([readonly]), textarea').forEach(input => {
                input.classList.remove('error', 'success');
            });
            
            // Validate description
            const description = document.getElementById('description');
            if (description && description.value.trim() === '') {
                document.getElementById('descriptionError').style.display = 'block';
                document.getElementById('descriptionError').textContent = 'Please provide a description';
                description.classList.add('error');
                isValid = false;
                errors.push('Please provide a description');
            } else if (description && description.value.trim().length < 20) {
                document.getElementById('descriptionError').style.display = 'block';
                document.getElementById('descriptionError').textContent = 'Description must be at least 20 characters';
                description.classList.add('error');
                isValid = false;
                errors.push('Description must be at least 20 characters');
            } else if (description) {
                description.classList.add('success');
            }
            
            return {isValid, errors};
        }
        
        // Form submission handling
        document.getElementById('organizerRegistrationForm')?.addEventListener('submit', function(e) {
            const validationResult = validateForm();
            
            if (!validationResult.isValid) {
                e.preventDefault();
                alert('Please correct the errors in the form:\n\n' + validationResult.errors.join('\n'));
            } else {
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span>Registering...</span>';
                    submitBtn.classList.add('btn-disabled');
                    submitBtn.disabled = true;
                }
            }
        });
        
        // Real-time validation of description field
        document.getElementById('description')?.addEventListener('blur', function() {
            const description = document.getElementById('description');
            if (description.value.trim() === '' || description.value.trim().length < 20) {
                document.getElementById('descriptionError').style.display = 'block';
                document.getElementById('descriptionError').textContent = description.value.trim() === '' ? 
                    'Please provide a description' : 'Description must be at least 20 characters';
                description.classList.add('error');
                description.classList.remove('success');
            } else {
                document.getElementById('descriptionError').style.display = 'none';
                description.classList.remove('error');
                description.classList.add('success');
            }
        });
        
        // Initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar interaction (same as homepage)
            document.querySelectorAll('.nav-btn').forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all links
                    document.querySelectorAll('.nav-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to current link
                    this.classList.add('active');
                    
                    // Get page name
                    const pageName = this.querySelector('span:not(.icon)').textContent;
                    console.log(`Navigating to: ${pageName}`);
                });
            });

            // Mobile sidebar toggle
            let isSidebarOpen = false;
            function toggleSidebar() {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.toggle('active');
                isSidebarOpen = !isSidebarOpen;
            }

            
            
            // Page load animation
            window.addEventListener('load', () => {
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.5s ease';
                
                setTimeout(() => {
                    document.body.style.opacity = '1';
                }, 100);
                
               
              
            });
        });
    </script>   
</body>
</html>