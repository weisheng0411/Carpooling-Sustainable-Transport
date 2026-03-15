<?php
session_start();

// Database connection configuration
$host = 'localhost';
$dbname = 'wdd';
$username = 'root';
$password = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Create database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get current user information
$user_id = $_SESSION['user_id'];

// Initialize variables
$organizer_id = null;
$organizer_status = null;
$organizer_name = null;
$organizer_email = null;
$is_organizer = false;
$can_create_event = false;
$error_message = '';
$success = false;

// Check if user is an organizer
try {
    $organizer_query = "SELECT o.Organizer_ID, o.Status, u.name, u.email 
                       FROM organizer o 
                       JOIN user_acc u ON o.User_ID = u.user_id 
                       WHERE o.User_ID = :user_id";
    $organizer_stmt = $conn->prepare($organizer_query);
    $organizer_stmt->bindParam(':user_id', $user_id);
    $organizer_stmt->execute();
    
    if ($organizer_stmt->rowCount() > 0) {
        $organizer = $organizer_stmt->fetch(PDO::FETCH_ASSOC);
        $organizer_id = $organizer['Organizer_ID'];
        $organizer_status = trim($organizer['Status']);
        $organizer_name = $organizer['name'];
        $organizer_email = $organizer['email'];
        $is_organizer = true;
        
       
        if (strtolower($organizer_status) === 'pass') {
            $can_create_event = true;
        }
    }
} catch (PDOException $e) {
    $error_message = "Failed to get organizer information: " . $e->getMessage();
}

// Handle form submission - only approved organizers can submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_create_event) {
    try {
        // Get form data
        $event_name = $_POST['event_name'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        $reward_points = $_POST['reward_points'] ?? 0;
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $max_members = $_POST['max_members'] ?? 0;
        $event_rules = $_POST['event_rules'] ?? '';
        
        // Validate required fields
        if (empty($event_name) || empty($location) || empty($description) || 
            empty($start_time) || empty($end_time) || empty($contact)) {
            $error_message = "Please fill in all required fields";
        } elseif ($end_time <= $start_time) {
            $error_message = "End time must be after start time";
        } elseif ($reward_points < 0 || $reward_points > 1000) {
            $error_message = "Reward points must be between 0 and 1000";
        } elseif ($max_members < 1 || $max_members > 1000) {
            $error_message = "Maximum members must be between 1 and 1000";
        } else {
            
            $start_date_obj = new DateTime($start_time);
            $end_date_obj = new DateTime($end_time);
            $interval = $start_date_obj->diff($end_date_obj);
            $duration_days = $interval->days;
            
            
            $photo_url = null;
            if (isset($_FILES['event_photo']) && $_FILES['event_photo']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/events/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['event_photo']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '_' . date('Ymd_His') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
                $file_type = $_FILES['event_photo']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    // Validate file size (max 5MB)
                    if ($_FILES['event_photo']['size'] <= 5 * 1024 * 1024) {
                        if (move_uploaded_file($_FILES['event_photo']['tmp_name'], $target_path)) {
                            $photo_url = $target_path;
                        } else {
                            $error_message = "Failed to upload event photo";
                        }
                    } else {
                        $error_message = "Event photo must be less than 5MB";
                    }
                } else {
                    $error_message = "Only JPG, PNG, GIF, and WebP images are allowed";
                }
            } else {
                $error_message = "Please upload an event photo";
            }
            
            if (empty($error_message)) {
                
                $insert_query = "INSERT INTO event 
                                (Name, Description, Rule, Start_Date, End_Date, Duration_Days, 
                                Location, Points, Photo_URL, Max_member, Status, Organizer_ID) 
                                VALUES 
                                (:name, :description, :rule, :start_date, :end_date, :duration_days, 
                                :location, :points, :photo_url, :max_member, :status, :organizer_id)";
                
                $insert_stmt = $conn->prepare($insert_query);
                
                $result = $insert_stmt->execute([
                    ':name' => $event_name,
                    ':description' => $description,
                    ':rule' => $event_rules,
                    ':start_date' => $start_time,
                    ':end_date' => $end_time,
                    ':duration_days' => $duration_days,
                    ':location' => $location,
                    ':points' => $reward_points,
                    ':photo_url' => $photo_url,
                    ':max_member' => $max_members,
                    ':status' => 'Pending',
                    ':organizer_id' => $organizer_id
                ]);
                
                if ($result) {
                    // Redirect to event.php and show success message
                    header('Location: event.php?success=1');
                    exit();
                } else {
                    $error_message = "Failed to create event. Please try again.";
                }
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// If redirected after submission, show success message
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - EcoCommute</title>
    <style>
        /* ========== Global Variables ========== */
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            background: var(--card-bg);
            width: 90%;
            max-width: 900px;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-top: 5px solid var(--light-green);
        }

        h2 {
            color: var(--text-dark);
            margin-bottom: 15px;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        hr {
            background: linear-gradient(to right, transparent, var(--light-green), transparent);
            border: none;
            height: 2px;
            margin-bottom: 25px;
            border-radius: 1px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label::before {
            content: "•";
            color: var(--light-green);
            font-size: 18px;
        }

        .full {
            grid-column: span 2;
        }

        input, textarea, select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 8px;
            border: 2px solid #c8e6c9;
            background: #f8fff8;
            font-size: 16px;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            background: white;
        }

        input::placeholder, textarea::placeholder {
            color: #9e9e9e;
        }

        textarea {
            resize: none;
            min-height: 120px;
            font-family: inherit;
            line-height: 1.5;
        }

        .submit, .cancel {
            margin-top: 15px;
            justify-self: center;
            width: 80%;
            border: none;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            padding: 16px;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .submit {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
        }

        .cancel {
            background: linear-gradient(135deg, #666 0%, #333 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #555 0%, #222 100%);
        }

        .submit:active, .cancel:active {
            transform: translateY(0);
        }

        /* File upload styles */
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-container input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 16px;
            background: #f1f8e9;
            border: 2px dashed #c8e6c9;
            border-radius: 8px;
            color: var(--text-medium);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-input-label:hover {
            background: #e8f5e9;
            border-color: var(--light-green);
        }

        .file-input-label span {
            font-size: 20px;
        }

        /* Form instructions */
        .form-note {
            grid-column: span 2;
            background: #f8fff8;
            border-left: 4px solid var(--light-green);
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: var(--text-medium);
        }

        .form-note p {
            margin: 0 0 8px 0;
        }

        .form-note ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-note li {
            margin-bottom: 5px;
        }

        /* Error message styles */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
            grid-column: span 2;
            display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;
        }

        .error-message.show {
            display: block;
        }

        /* Permission warning styles */
        .permission-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ffeaa7;
            margin-bottom: 20px;
            text-align: center;
        }

        .permission-warning h3 {
            margin-bottom: 10px;
            color: #856404;
        }

        .permission-warning p {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .permission-warning .status {
            font-weight: bold;
            color: var(--text-dark);
            background: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin: 5px 0;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            body {
                padding: 20px;
            }
            
            .container {
                padding: 30px 25px;
            }
            
            form {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .full {
                grid-column: span 1;
            }
            
            .submit, .cancel {
                width: 100%;
            }
            
            h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 25px 20px;
            }
            
            input, textarea, select {
                padding: 12px 14px;
            }
            
            .file-input-label {
                padding: 12px 14px;
                font-size: 14px;
            }
        }

        /* Page load animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Event</h2>
    <hr>
    
    <?php if (!$is_organizer): ?>
        <!-- User is not an organizer -->
        <div class="permission-warning">
            <h3>⚠️ Registration Required</h3>
            <p>You are not registered as an event organizer.</p>
            <p>Please register as an event organizer first to create events.</p>
            <a href="register_event_organizer.php" class="btn-register">
                Register as Organizer
            </a>
        </div>
        
    <?php elseif (!$can_create_event): ?>
       
        <div class="permission-warning">
            <h3>⚠️ Pending Approval</h3>
            <p>Your organizer account is pending approval.</p>
            <p>Current status: <span class="status"><?php echo htmlspecialchars($organizer_status); ?></span></p>
            <p>You can view events but cannot create new events until your account is approved by admin.</p>
            <p style="margin-top: 15px;">
                <a href="event.php" class="btn-register">View Events</a>
            </p>
        </div>
        
    <?php else: ?>
        <!-- Approved organizers can see the form -->
        
        <!-- Error message -->
        <?php if (!empty($error_message)): ?>
        <div class="error-message show" id="errorMessage">
            <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="eventForm">
            
            <div class="form-group">
                <label>Event Name</label>
                <input type="text" name="event_name" placeholder="Enter event name" required 
                       value="<?php echo isset($_POST['event_name']) ? htmlspecialchars($_POST['event_name']) : ''; ?>">
            </div>

            <!-- Location -->
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="Enter event location" required 
                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" placeholder="Brief description of the event" required 
                       value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>">
            </div>

            <!-- Reward Points -->
            <div class="form-group">
                <label>Reward Points</label>
                <input type="number" name="reward_points" min="0" max="1000" placeholder="e.g. 100" required 
                       value="<?php echo isset($_POST['reward_points']) ? htmlspecialchars($_POST['reward_points']) : '100'; ?>">
            </div>

            <!-- Start Time -->
            <div class="form-group">
                <label>Start Time</label>
                <input type="datetime-local" name="start_time" required 
                       value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
            </div>

            <!-- End Time -->
            <div class="form-group">
                <label>End Time</label>
                <input type="datetime-local" name="end_time" required 
                       value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
            </div>

            <!-- Contact -->
            <div class="form-group">
                <label>Contact Event Organizer</label>
                <input type="tel" name="contact" placeholder="Phone number or email" required 
                       value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : htmlspecialchars($organizer_email ?? ''); ?>">
            </div>

            <!-- Maximum Members -->
            <div class="form-group">
                <label>Maximum Members</label>
                <input type="number" name="max_members" min="1" max="1000" placeholder="e.g. 50" required 
                       value="<?php echo isset($_POST['max_members']) ? htmlspecialchars($_POST['max_members']) : '50'; ?>">
            </div>

            <!-- Event Rules -->
            <div class="form-group full">
                <label>Event Rules</label>
                <textarea name="event_rules" rows='4' placeholder="List the rules for participants..." required><?php echo isset($_POST['event_rules']) ? htmlspecialchars($_POST['event_rules']) : ''; ?></textarea>
            </div>

            <!-- Upload Photo -->
            <div class="form-group full">
                <label>Upload Event Photo</label>
                <div class="file-input-container">
                    <input type="file" name="event_photo" accept="image/*" required id="eventPhoto">
                    <div class="file-input-label" id="fileInputLabel">
                        <span>📷</span>
                        <span>Click to upload event photo</span>
                    </div>
                </div>
            </div>

            <!-- Form notes -->
            <div class="form-note">
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>All events require admin approval before being published</li>
                    <li>Ensure all information is accurate and complete</li>
                    <li>Events must comply with EcoCommute community guidelines</li>
                    <li>You will be notified via email once your event is approved</li>
                    <li>Approval process typically takes 2-3 business days</li>
                </ul>
            </div>

            <!-- Submit button -->
            <button class="submit full" type="submit" id="submitBtn">
                <span>Submit for Approval</span>
                <span>✓</span>
            </button>
            
            <!-- Cancel button -->
            <button class="cancel full" type="button" onclick="window.location.href='event.php'">
                <span>Cancel</span>
                <span>✕</span>
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    // File upload preview
    document.getElementById('eventPhoto').addEventListener('change', function(e) {
        const label = document.getElementById('fileInputLabel');
        if (this.files && this.files[0]) {
            label.innerHTML = `<span>📷</span><span>${this.files[0].name}</span>`;
            label.style.background = '#e8f5e9';
            label.style.borderColor = '#4caf50';
            label.style.color = '#2e7d32';
        }
    });

    // Form validation
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        const startTime = document.querySelector('input[name="start_time"]').value;
        const endTime = document.querySelector('input[name="end_time"]').value;
        const rewardPoints = parseInt(document.querySelector('input[name="reward_points"]').value);
        const maxMembers = parseInt(document.querySelector('input[name="max_members"]').value);
        const eventPhoto = document.getElementById('eventPhoto').files[0];
        
        // Hide previous error messages
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) errorMessage.classList.remove('show');
        
        let hasError = false;
        let errorMsg = '';
        
        // Validate time
        if (startTime && endTime) {
            const start = new Date(startTime);
            const end = new Date(endTime);
            
            if (end <= start) {
                errorMsg = 'End time must be after start time!';
                hasError = true;
            }
        }
        
        // Validate points
        if (rewardPoints < 0 || rewardPoints > 1000) {
            errorMsg = 'Reward points must be between 0 and 1000!';
            hasError = true;
        }
        
        // Validate max members
        if (maxMembers < 1 || maxMembers > 1000) {
            errorMsg = 'Maximum members must be between 1 and 1000!';
            hasError = true;
        }
        
        // Validate file
        if (!eventPhoto) {
            errorMsg = 'Please upload an event photo!';
            hasError = true;
        } else if (eventPhoto.size > 5 * 1024 * 1024) { // 5MB
            errorMsg = 'Event photo must be less than 5MB!';
            hasError = true;
        }
        
        if (hasError) {
            e.preventDefault();
            // Show error message
            if (!errorMessage) {
                // Create error message element
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message show';
                errorDiv.id = 'errorMessage';
                errorDiv.innerHTML = `<strong>⚠️ Error:</strong> ${errorMsg}`;
                document.querySelector('.container').insertBefore(errorDiv, document.querySelector('form'));
            } else {
                errorMessage.innerHTML = `<strong>⚠️ Error:</strong> ${errorMsg}`;
                errorMessage.classList.add('show');
            }
            
            // Scroll to error message
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return false;
        }
        
        // Show submitting state
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<span>Submitting...</span><span>⌛</span>';
        submitBtn.disabled = true;
        
        return true;
    });

    // Page load effect
    window.addEventListener('load', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease';
        
        setTimeout(function() {
            document.body.style.opacity = '1';
        }, 100);
    });


</script>

</body>
</html>