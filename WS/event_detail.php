<?php
    // Start session
    session_start();

    // Database connection configuration
    $host = 'localhost';
    $dbname = 'wdd';
    $username = 'root';
    $password = ''; // Please modify according to your actual configuration

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // Get event ID
    $event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Initialize variables
    $event = null;
    $participants = [];
    $organizer = null;
    $is_registered = false;
    $registration_count = 0;
    $user_registration_id = null;
    $db_error = false;
    $user = null;
    $unread_count = 0;
    $display_status = null; // Status for frontend display
    $is_event_organizer = false; // Check if current user is event creator
    $can_register = false; // Whether registration is allowed

    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Get current user information
        $user_id = $_SESSION['user_id'];
        $user_query = "SELECT user_id, apu_id, name, username, email, phone_number, 
                            gender, photo_url, current_point, summary_point, role 
                    FROM user_acc 
                    WHERE user_id = :user_id 
                    LIMIT 1";
        
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        
        if ($user_stmt->rowCount() > 0) {
            $user = $user_stmt->fetch();
        } else {
            session_destroy();
            header('Location: login.php');
            exit();
        }
        
        // Get event details
        if ($event_id > 0) {
            $event_query = "SELECT e.Event_ID, e.Name, e.Description, e.Rule, 
                                e.Start_Date, e.End_Date, e.Duration_Days, e.Location, 
                                e.Points, e.Photo_URL, e.Max_member, e.Status, 
                                e.Organizer_ID, u.name as organizer_name,
                                u.photo_url as organizer_photo
                            FROM event e
                            LEFT JOIN user_acc u ON e.Organizer_ID = u.user_id
                            WHERE e.Event_ID = :event_id";
            
            $event_stmt = $conn->prepare($event_query);
            $event_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $event_stmt->execute();
            
            if ($event_stmt->rowCount() > 0) {
                $event = $event_stmt->fetch();
                
                // Check if current user is event creator
                if ($event['Organizer_ID'] == $user_id) {
                    $is_event_organizer = true;
                }
                
                // Check user permissions: if not event creator and event status is pending, deny access
                if (strtolower($event['Status']) === 'pending' && !$is_event_organizer) {
                    // Redirect to events list page
                    header('Location: event.php');
                    exit();
                }
                
                // Initialize display status to database status
                $display_status = $event['Status'];
                
                // Check if registration is allowed: only active status and not registered
                $can_register = (strtolower($event['Status']) === 'active' && !$is_registered);
                
                // Get organizer information
                try {
                    if (!empty($event['Organizer_ID'])) {
                        $organizer_query = "SELECT name, photo_url FROM user_acc WHERE user_id = :organizer_id";
                        $organizer_stmt = $conn->prepare($organizer_query);
                        $organizer_stmt->bindParam(':organizer_id', $event['Organizer_ID'], PDO::PARAM_INT);
                        $organizer_stmt->execute();
                        if ($organizer_stmt->rowCount() > 0) {
                            $organizer = $organizer_stmt->fetch();
                        }
                    }
                } catch(PDOException $e) {
                    error_log("Organizer query error: " . $e->getMessage());
                    $organizer = null;
                }
                
                // Get participant information
                try {
                    $participants_query = "SELECT r.Reg_ID, r.Participant_ID, r.User_ID,
                                                u.name, u.photo_url, u.current_point
                                        FROM registration r
                                        JOIN user_acc u ON r.User_ID = u.user_id
                                        WHERE r.Reg_ID IN (
                                            SELECT ed.Reg_ID 
                                            FROM event_details ed 
                                            WHERE ed.Event_ID = :event_id
                                        )";
                    
                    $participants_stmt = $conn->prepare($participants_query);
                    $participants_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $participants_stmt->execute();
                    $participants = $participants_stmt->fetchAll();
                    $registration_count = count($participants);
                } catch(PDOException $e) {
                    error_log("Participants query error: " . $e->getMessage());
                    $participants = [];
                    $registration_count = 0;
                }
                
                // Check if current user is already registered
                try {
                    $check_reg_query = "SELECT r.Reg_ID 
                                        FROM registration r
                                        JOIN event_details ed ON r.Reg_ID = ed.Reg_ID
                                        WHERE r.User_ID = :user_id 
                                        AND ed.Event_ID = :event_id";
                    
                    $check_reg_stmt = $conn->prepare($check_reg_query);
                    $check_reg_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $check_reg_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $check_reg_stmt->execute();
                    
                    if ($check_reg_stmt->rowCount() > 0) {
                        $is_registered = true;
                        $reg_data = $check_reg_stmt->fetch();
                        $user_registration_id = $reg_data['Reg_ID'];
                        
                        // If user is registered, display status as "Joined"
                        $display_status = 'Joined';
                    }
                } catch(PDOException $e) {
                    error_log("Registration check error: " . $e->getMessage());
                    $is_registered = false;
                }
                
                // Re-check if registration is allowed (considering registration status)
                $can_register = (strtolower($event['Status']) === 'active' && !$is_registered && $registration_count < $event['Max_member']);
                
                // Get unread notification count
                try {
                    $notif_query = "SELECT COUNT(*) as total_count FROM notification WHERE user_id = :user_id AND is_read = 0";
                    $notif_stmt = $conn->prepare($notif_query);
                    $notif_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $notif_stmt->execute();
                    $notification = $notif_stmt->fetch();
                    $unread_count = $notification['total_count'] ?? 0;
                } catch(PDOException $e) {
                    error_log("Notification query error: " . $e->getMessage());
                    $unread_count = 0;
                }
                
            } else {
                $event = null;
            }
        }
        
    } catch(PDOException $e) {
        error_log("Main database error in event_detail.php: " . $e->getMessage());
        $db_error = true;
    }

    // Handle event registration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $event && !$db_error) {
        try {
            if ($_POST['action'] === 'register' && $event_id > 0 && !$is_registered) {
                // Re-check if registration is allowed
                if (strtolower($event['Status']) !== 'active') {
                    $_SESSION['message'] = "Cannot register for this event. Event status must be active.";
                    $_SESSION['message_type'] = 'error';
                    header("Location: event_detail.php?id=$event_id");
                    exit();
                }
                
                // Check if there are still spots available
                if ($registration_count < $event['Max_member']) {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Insert into registration table
                    $insert_reg_query = "INSERT INTO registration (Participant_ID, User_ID) 
                                        VALUES (:participant_id, :user_id)";
                    $insert_reg_stmt = $conn->prepare($insert_reg_query);
                    $insert_reg_stmt->bindParam(':participant_id', $user_id, PDO::PARAM_INT);
                    $insert_reg_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $insert_reg_stmt->execute();
                    
                    $reg_id = $conn->lastInsertId();
                    
                    // Insert into event_details table
                    $insert_ed_query = "INSERT INTO event_details (Event_ID, Reg_ID) 
                                        VALUES (:event_id, :reg_id)";
                    $insert_ed_stmt = $conn->prepare($insert_ed_query);
                    $insert_ed_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $insert_ed_stmt->bindParam(':reg_id', $reg_id, PDO::PARAM_INT);
                    $insert_ed_stmt->execute();
                    
                    // Get user's current point values
                    $check_points_query = "SELECT COALESCE(current_point, 0) as current_point, 
                                                COALESCE(summary_point, 0) as summary_point 
                                        FROM user_acc 
                                        WHERE user_id = :user_id";
                    $check_points_stmt = $conn->prepare($check_points_query);
                    $check_points_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $check_points_stmt->execute();
                    $current_points = $check_points_stmt->fetch();
                    
                    if ($current_points) {
                        $new_current = $current_points['current_point'] + $event['Points'];
                        $new_summary = $current_points['summary_point'] + $event['Points'];
                        
                        // Update points
                        $update_points_query = "UPDATE user_acc 
                                            SET current_point = :new_current,
                                                summary_point = :new_summary 
                                            WHERE user_id = :user_id";
                        $update_points_stmt = $conn->prepare($update_points_query);
                        $update_points_stmt->bindParam(':new_current', $new_current, PDO::PARAM_INT);
                        $update_points_stmt->bindParam(':new_summary', $new_summary, PDO::PARAM_INT);
                        $update_points_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $update_points_stmt->execute();
                    } else {
                        throw new Exception("User not found when updating points");
                    }
                    
                    $conn->commit();
                    
                    // Set success message and status
                    $_SESSION['message'] = "Successfully registered for the event! You earned {$event['Points']} points.";
                    $_SESSION['message_type'] = 'success';
                    
                    // Refresh page, force re-check registration status
                    header("Location: event_detail.php?id=$event_id&joined=1");
                    exit();
                    
                } else {
                    $_SESSION['message'] = "Sorry, this event is full!";
                    $_SESSION['message_type'] = 'error';
                }
                
            } elseif ($_POST['action'] === 'cancel' && $user_registration_id) {
                // Check event status: if not active, cancellation not allowed
                if (strtolower($event['Status']) !== 'active') {
                    $_SESSION['message'] = "Cannot cancel registration for this event.";
                    $_SESSION['message_type'] = 'error';
                    header("Location: event_detail.php?id=$event_id");
                    exit();
                }
                
                // Cancel registration
                $conn->beginTransaction();
                
                // Delete from event_details
                $delete_ed_query = "DELETE FROM event_details 
                                    WHERE Event_ID = :event_id 
                                    AND Reg_ID = :reg_id";
                $delete_ed_stmt = $conn->prepare($delete_ed_query);
                $delete_ed_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                $delete_ed_stmt->bindParam(':reg_id', $user_registration_id, PDO::PARAM_INT);
                $delete_ed_stmt->execute();
                
                // Delete from registration
                $delete_reg_query = "DELETE FROM registration 
                                    WHERE Reg_ID = :reg_id";
                $delete_reg_stmt = $conn->prepare($delete_reg_query);
                $delete_reg_stmt->bindParam(':reg_id', $user_registration_id, PDO::PARAM_INT);
                $delete_reg_stmt->execute();
                
                // Deduct points
                $check_points_query = "SELECT COALESCE(current_point, 0) as current_point,
                                            COALESCE(summary_point, 0) as summary_point 
                                    FROM user_acc 
                                    WHERE user_id = :user_id";
                $check_points_stmt = $conn->prepare($check_points_query);
                $check_points_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $check_points_stmt->execute();
                $current_points = $check_points_stmt->fetch();
                
                if ($current_points) {
                    // Ensure points don't become negative
                    $new_current = max(0, $current_points['current_point'] - $event['Points']);
                    $new_summary = max(0, $current_points['summary_point'] - $event['Points']);
                    
                    // Update both current_point and summary_point
                    $deduct_points_query = "UPDATE user_acc 
                                        SET current_point = :new_current,
                                            summary_point = :new_summary 
                                        WHERE user_id = :user_id";
                    $deduct_points_stmt = $conn->prepare($deduct_points_query);
                    $deduct_points_stmt->bindParam(':new_current', $new_current, PDO::PARAM_INT);
                    $deduct_points_stmt->bindParam(':new_summary', $new_summary, PDO::PARAM_INT);
                    $deduct_points_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $deduct_points_stmt->execute();
                } else {
                    throw new Exception("User not found when deducting points");
                }
                
                $conn->commit();
                
                $_SESSION['message'] = "Registration cancelled successfully.";
                $_SESSION['message_type'] = 'success';
                
                header("Location: event_detail.php?id=$event_id&cancelled=1");
                exit();
            }
            
        } catch(Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                try {
                    $conn->rollBack();
                } catch(Exception $rollback_e) {
                    error_log("Rollback failed: " . $rollback_e->getMessage());
                }
            }
            
            // Log detailed error information
            error_log("Registration/cancellation error: " . $e->getMessage());
            if ($e instanceof PDOException) {
                error_log("PDO Error Info: " . print_r($e->errorInfo, true));
            }
            
            // Provide user-friendly error message
            $_SESSION['message'] = "Operation failed. Please try again.";
            $_SESSION['message_type'] = 'error';
            
            header("Location: event_detail.php?id=$event_id");
            exit();
        }
    }

    // Check URL parameters, if user just registered, force display "Joined" status
    if (isset($_GET['joined']) && $_GET['joined'] == '1') {
        $is_registered = true;
        $display_status = 'Joined';
        $can_register = false;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - EcoCommute</title>
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
            --joined-blue: #2196f3;
            --joined-light-blue: #e3f2fd;
            --warning-orange: #ff9800;
            --warning-light: #fff3e0;
            --pending-gray: #757575;
            --pending-light: #f5f5f5;
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

        hr {
            border: none;
            height: 1px;
            background: #ddd;
            margin: 20px 0 30px 0;
        }
        
        /* ========== Event Detail Page Content ========== */
        .event-detail-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        .event-main-content {
            flex: 2;
        }
        
        .event-sidebar {
            flex: 1;
        }
        
        .event-detail-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .event-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 10px 0;
            line-height: 1.2;
        }
        
        .event-organizer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .organizer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .organizer-info h4 {
            margin: 0;
            font-size: 16px;
            color: var(--text-dark);
        }
        
        .organizer-info p {
            margin: 0;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .event-points {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .event-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background-color: #f8f9f8;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--light-green);
        }
        
        .info-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .event-description {
            background-color: #f8f9f8;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .description-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .description-content {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-medium);
        }
        
        .description-content p {
            margin: 0 0 15px 0;
        }
        
        .description-content ul, 
        .description-content ol {
            margin: 10px 0 15px 20px;
            padding: 0;
        }
        
        .description-content li {
            margin-bottom: 8px;
        }
        
        /* Sidebar Content */
        .event-actions {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .event-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: var(--eco-green);
        }
        
        .status-joined {
            background-color: #e3f2fd;
            color: var(--joined-blue);
        }
        
        .status-upcoming {
            background-color: #fff3e0;
            color: var(--warning-orange);
        }
        
        .status-pending {
            background-color: var(--pending-light);
            color: var(--pending-gray);
            border: 1px dashed #ddd;
        }
        
        .participants-count {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 25px;
        }
        
        .count-number {
            font-weight: 600;
            color: var(--eco-green);
            font-size: 18px;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
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
        
        .btn-warning {
            background-color: #ff9800;
            color: white;
            border: none;
        }
        
        .btn-warning:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        
        .btn-success {
            background-color: #4caf50;
            color: white;
            border: none;
            cursor: default;
        }
        
        .btn-disabled {
            background-color: #9e9e9e;
            color: white;
            border: none;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        .loading-spinner.warning {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .event-tags {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }
        
        .tags-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tag {
            background-color: #e8f5e9;
            color: var(--eco-green);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Participants List */
        .participants-section {
            margin-top: 25px;
        }
        
        .participants-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .participant-card {
            background-color: #f8f9f8;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .participant-info h4 {
            margin: 0;
            font-size: 14px;
            color: var(--text-dark);
        }
        
        .participant-info p {
            margin: 0;
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Message Notifications */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }
        
        .message-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .message-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .message-warning {
            background-color: #fff3e0;
            color: #ef6c00;
            border-left: 4px solid #ff9800;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Quick Confirmation Dialog */
        .quick-confirm {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .confirm-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .confirm-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .confirm-message {
            margin-bottom: 25px;
            color: var(--text-medium);
            line-height: 1.5;
        }
        
        .confirm-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .confirm-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            min-width: 80px;
        }
        
        .confirm-cancel {
            background: #f5f5f5;
            color: #666;
        }
        
        .confirm-cancel:hover {
            background: #e0e0e0;
        }
        
        .confirm-ok {
            background: var(--eco-green);
            color: white;
        }
        
        .confirm-ok:hover {
            background: #1b5e20;
        }
        
        .quick-confirm.hidden {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 25px;
            }
            
            .event-detail-container {
                flex-direction: column;
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
            
            .event-info-grid {
                grid-template-columns: 1fr;
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
            
            .event-detail-card {
                padding: 20px;
            }
            
            .event-title {
                font-size: 24px;
            }
            
            .event-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .confirm-box {
                width: 95%;
                padding: 20px;
            }
            
            .confirm-actions {
                flex-direction: column;
            }
            
            .confirm-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Design -->
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
                    <a href="event.php" class="nav-btn active">
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
                        <span class="icon">📈</span>
                        <span>Statistic/Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Registration</div>
                    <a href="register_event_organizer.php" class="nav-btn">
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
        
        <!-- Main Content Area -->
        <main class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h2>Event Details</h2>
                    <p>Explore event information and join the community activity! 🌿</p>
                </div>
            </div>
           
            <hr>
            
            <!-- Message Notifications -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message message-<?php echo $_SESSION['message_type']; ?>">
                    <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <!-- Database Error Message -->
            <?php if ($db_error && !$event): ?>
            <div class="message message-error">
                <span>⚠️ Database Connection Error. Unable to load event details.</span>
            </div>
            <?php elseif (!$event && $event_id > 0): ?>
                <div class="message message-error">
                    <span>Event not found! It may have been removed or the ID is incorrect.</span>
                    <p style="margin-top: 10px;">
                        <a href="event.php">Back to events list</a>
                    </p>
                </div>
            <?php elseif ($event_id <= 0): ?>
                <div class="message message-error">
                    <span>Invalid event ID. Please select a valid event.</span>
                    <p style="margin-top: 10px;">
                        <a href="event.php">Back to events list</a>
                    </p>
                </div>
            <?php else: ?>
            
            <!-- Event Detail Page Content -->
            <div class="event-detail-container">
                <!-- Left Main Content -->
                <div class="event-main-content">
                    <div class="event-detail-card">
                        <div class="event-header">
                            <div>
                                <h1 class="event-title"><?php echo htmlspecialchars($event['Name']); ?></h1>
                                <?php if ($organizer): ?>
                                <div class="event-organizer">
                                    <?php if (!empty($organizer['photo_url'])): ?>
                                        <img src="/ASS-WDD/<?php echo htmlspecialchars($organizer['photo_url']); ?>" class="organizer-avatar" alt="Organizer">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #4caf50; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($organizer['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="organizer-info">
                                        <h4><?php echo htmlspecialchars($organizer['name']); ?></h4>
                                        <p>Organizer</p>
                                        <?php if ($is_event_organizer): ?>
                                        <p style="font-size: 11px; color: #ff9800;">(You are the organizer)</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="event-organizer">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; color: #666; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        ?
                                    </div>
                                    <div class="organizer-info">
                                        <h4>Unknown Organizer</h4>
                                        <p>Organizer ID: <?php echo $event['Organizer_ID']; ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="event-points">
                                <span>+<?php echo $event['Points']; ?></span>
                                <span>Green Points</span>
                            </div>
                        </div>
                        
                        <!-- Event Information Grid -->
                        <div class="event-info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <span>📅</span>
                                    <span>Start Date</span>
                                </div>
                                <div class="info-value"><?php echo date('F j, Y', strtotime($event['Start_Date'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <span>📅</span>
                                    <span>End Date</span>
                                </div>
                                <div class="info-value"><?php echo date('F j, Y', strtotime($event['End_Date'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <span>📍</span>
                                    <span>Location</span>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($event['Location']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <span>⏰</span>
                                    <span>Duration</span>
                                </div>
                                <div class="info-value"><?php echo $event['Duration_Days']; ?> Days</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <span>👥</span>
                                    <span>Capacity</span>
                                </div>
                                <div class="info-value"><?php echo $registration_count; ?> / <?php echo $event['Max_member']; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <span>🏷️</span>
                                    <span>Status</span>
                                </div>
                                <div class="info-value" id="status-display"><?php echo htmlspecialchars($display_status); ?></div>
                            </div>
                        </div>
                        
                        <!-- Event Description -->
                        <div class="event-description">
                            <h3 class="description-title">
                                <span>📝</span>
                                <span>Description</span>
                            </h3>
                            <div class="description-content">
                                <?php echo nl2br(htmlspecialchars($event['Description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Event Rules -->
                        <?php if (!empty($event['Rule'])): ?>
                        <div class="event-description" style="margin-top: 20px;">
                            <h3 class="description-title">
                                <span>📋</span>
                                <span>Rules</span>
                            </h3>
                            <div class="description-content">
                                <?php echo nl2br(htmlspecialchars($event['Rule'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Participants List -->
                        <?php if (count($participants) > 0): ?>
                        <div class="participants-section">
                            <h3 class="description-title">
                                <span>👥</span>
                                <span>Participants (<?php echo count($participants); ?>)</span>
                            </h3>
                            <div class="participants-list">
                                <?php foreach ($participants as $participant): ?>
                                <div class="participant-card">
                                    <?php if (!empty($participant['photo_url'])): ?>
                                        <img src="/ASS-WDD/<?php echo htmlspecialchars($participant['photo_url']) ?>" class="participant-avatar" alt="Participant">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #2e7d32; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($participant['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="participant-info">
                                        <h4><?php echo htmlspecialchars($participant['name']); ?></h4>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Sidebar -->
                <div class="event-sidebar">
                    <!-- Action Buttons -->
                    <div class="event-actions">
                        <div class="event-status <?php 
                            echo 'status-' . strtolower($display_status);
                        ?>" id="status-tag">
                            <?php echo htmlspecialchars($display_status); ?>
                            <?php if (strtolower($display_status) === 'pending' && $is_event_organizer): ?>
                            <br><small style="font-size: 10px; font-weight: normal;">(Only visible to you)</small>
                            <?php endif; ?>
                        </div>
                        <div class="participants-count">
                            <span class="count-number"><?php echo $registration_count; ?></span> / <?php echo $event['Max_member']; ?> participants
                        </div>
                        
                        <form method="POST" action="" class="action-buttons" id="registration-form">
                            <?php if ($is_registered): ?>
                                
                                <button type="button" class="btn btn-success" disabled>
                                    <span>✓ Registered</span>
                                </button>
                                <?php if (strtolower($event['Status']) === 'active'): ?>
                                <button type="button" class="btn btn-warning" id="cancel-btn">
                                    <span>Cancel Registration</span>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-disabled" disabled>
                                    <span>Cannot Cancel (Event <?php echo strtolower($event['Status']); ?>)</span>
                                </button>
                                <?php endif; ?>
                            <?php elseif ($can_register): ?>
                                
                                <button type="button" class="btn btn-primary" id="joinEventBtn">
                                    <span>Join Event</span>
                                </button>
                            <?php elseif (strtolower($event['Status']) === 'pending'): ?>
                                <!-- Pending Status -->
                                <button type="button" class="btn btn-disabled" disabled>
                                    <span>Event Pending Approval</span>
                                </button>
                                <?php if ($is_event_organizer): ?>
                                <div style="font-size: 12px; color: #ff9800; margin-top: -10px; margin-bottom: 10px;">
                                    Only you can see this event until approved
                                </div>
                                <?php endif; ?>
                            <?php elseif (strtolower($event['Status']) === 'upcoming'): ?>
                                <!-- Upcoming Status -->
                                <button type="button" class="btn btn-disabled" disabled>
                                    <span>Event Coming Soon</span>
                                </button>
                            <?php elseif ($registration_count >= $event['Max_member']): ?>
                                <!-- Event Full Status -->
                                <button type="button" class="btn btn-disabled" disabled>
                                    <span>Event Full</span>
                                </button>
                            <?php else: ?>
                                <!-- Other non-registrable status -->
                                <button type="button" class="btn btn-disabled" disabled>
                                    <span>Event <?php echo strtolower($event['Status']); ?></span>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary" id="backBtn">
                                <span>Back to Events</span>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Tags -->
                    <div class="event-tags">
                        <h3 class="tags-title">Event Information</h3>
                        <div class="tags-container">
                            <span class="tag">Points: <?php echo $event['Points']; ?></span>
                            <span class="tag">Duration: <?php echo $event['Duration_Days']; ?> days</span>
                            <span class="tag">Max: <?php echo $event['Max_member']; ?> people</span>
                            <span class="tag" id="status-tag-small">Status: <?php echo htmlspecialchars($display_status); ?></span>
                            <?php if (strtolower($display_status) === 'pending' && $is_event_organizer): ?>
                            <span class="tag" style="background-color: #fff3e0; color: #ff9800;">Only visible to you</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Quick Confirmation Dialog -->
    <div class="quick-confirm hidden" id="quickConfirm">
        <div class="confirm-box">
            <h3 class="confirm-title" id="confirmTitle">Confirm Registration</h3>
            <p class="confirm-message" id="confirmMessage"></p>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn confirm-cancel" id="confirmCancel">Cancel</button>
                <button type="button" class="confirm-btn confirm-ok" id="confirmOk">Confirm</button>
            </div>
        </div>
    </div>
    
    <script>
        // Back button click handler
        document.getElementById('backBtn')?.addEventListener('click', function() {
            window.location.href = 'event.php';
        });
        
        // Quick confirmation dialog
        const quickConfirm = document.getElementById('quickConfirm');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmCancel = document.getElementById('confirmCancel');
        const confirmOk = document.getElementById('confirmOk');
        
        let currentAction = ''; // 'register' or 'cancel'
        let isSubmitting = false; // Prevent duplicate submission
        
        // Show confirmation dialog
        function showConfirm(action, message, title = 'Confirm Action') {
            if (isSubmitting) return;
            
            currentAction = action;
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            quickConfirm.classList.remove('hidden');
            
            // Prevent background scrolling
            document.body.style.overflow = 'hidden';
        }
        
        // Hide confirmation dialog
        function hideConfirm() {
            quickConfirm.classList.add('hidden');
            document.body.style.overflow = '';
        }
        
        // Join button click handler
        document.getElementById('joinEventBtn')?.addEventListener('click', function() {
            if (isSubmitting) return;
            
            showConfirm(
                'register',
                `Are you sure you want to join this event? You will earn <?php echo $event['Points']; ?> green points.`,
                'Join Event'
            );
        });
        
        // Cancel button click handler
        document.getElementById('cancel-btn')?.addEventListener('click', function() {
            if (isSubmitting) return;
            
            showConfirm(
                'cancel',
                `Are you sure you want to cancel your registration? Your <?php echo $event['Points']; ?> points will be deducted.`,
                'Cancel Registration'
            );
        });
        
        // Confirm button click handler
        confirmOk.addEventListener('click', function() {
            if (isSubmitting) return;
            
            isSubmitting = true;
            
            // Show loading state
            if (currentAction === 'register') {
                const joinBtn = document.getElementById('joinEventBtn');
                joinBtn.innerHTML = '<span class="loading-spinner"></span> Joining...';
                joinBtn.disabled = true;
                joinBtn.classList.remove('btn-primary');
                joinBtn.classList.add('btn-disabled');
            } else if (currentAction === 'cancel') {
                const cancelBtn = document.getElementById('cancel-btn');
                cancelBtn.innerHTML = '<span class="loading-spinner warning"></span> Cancelling...';
                cancelBtn.disabled = true;
                cancelBtn.classList.remove('btn-warning');
                cancelBtn.classList.add('btn-disabled');
            }
            
            // Hide confirmation dialog
            hideConfirm();
            
            // Create hidden form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = currentAction;
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        });
        
        // Cancel button click handler
        confirmCancel.addEventListener('click', function() {
            hideConfirm();
        });
        
        // Click background to close confirmation dialog
        quickConfirm.addEventListener('click', function(e) {
            if (e.target === quickConfirm) {
                hideConfirm();
            }
        });
        
        // Check URL parameters after page load
        window.addEventListener('load', function() {
            // Check if URL has joined parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('joined') && urlParams.get('joined') === '1') {
                // Update all status displays to "Joined"
                updateStatusToJoined();
                
                // Show success message
                showSuccessMessage();
                
                // Update button state
                updateButtonToRegistered();
                
                // Clear URL parameters to avoid triggering again on refresh
                window.history.replaceState({}, document.title, window.location.pathname + '?id=' + <?php echo $event_id; ?>);
            }
            
            // Check if URL has cancelled parameter
            if (urlParams.has('cancelled') && urlParams.get('cancelled') === '1') {
                // Show cancellation success message
                showCancelledMessage();
                
                // Clear URL parameters
                window.history.replaceState({}, document.title, window.location.pathname + '?id=' + <?php echo $event_id; ?>);
            }
        });
        
        // Update status display to "Joined"
        function updateStatusToJoined() {
            // Update status display
            const statusDisplay = document.getElementById('status-display');
            if (statusDisplay) {
                statusDisplay.textContent = 'Joined';
                statusDisplay.style.color = '#1565c0';
            }
            
            // Update status tag
            const statusTag = document.getElementById('status-tag');
            if (statusTag) {
                statusTag.textContent = 'Joined';
                statusTag.className = 'event-status status-joined';
            }
            
            // Update status tag in small tag
            const statusTagSmall = document.getElementById('status-tag-small');
            if (statusTagSmall) {
                statusTagSmall.textContent = 'Status: Joined';
                statusTagSmall.style.backgroundColor = '#e3f2fd';
                statusTagSmall.style.color = '#1565c0';
            }
        }
        
        // Show success message
        function showSuccessMessage() {
            const successMessage = document.createElement('div');
            successMessage.className = 'message message-success';
            successMessage.innerHTML = '<span>✅ Successfully registered for the event! You earned <?php echo $event['Points']; ?> points.</span>';
            
            const main = document.querySelector('.main');
            if (main) {
                main.insertBefore(successMessage, main.firstChild);
                
                // Auto-remove message after 5 seconds
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    setTimeout(() => successMessage.remove(), 500);
                }, 5000);
            }
        }
        
        // Show cancellation success message
        function showCancelledMessage() {
            const cancelledMessage = document.createElement('div');
            cancelledMessage.className = 'message message-warning';
            cancelledMessage.innerHTML = '<span>⚠️ Registration cancelled successfully. Points have been deducted.</span>';
            
            const main = document.querySelector('.main');
            if (main) {
                main.insertBefore(cancelledMessage, main.firstChild);
                
                // Auto-remove message after 5 seconds
                setTimeout(() => {
                    cancelledMessage.style.opacity = '0';
                    cancelledMessage.style.transform = 'translateY(-10px)';
                    setTimeout(() => cancelledMessage.remove(), 500);
                }, 5000);
            }
        }
        
        // Update button state
        function updateButtonToRegistered() {
            const joinBtn = document.getElementById('joinEventBtn');
            if (joinBtn) {
                // Create Registered button
                const registeredBtn = document.createElement('button');
                registeredBtn.type = 'button';
                registeredBtn.className = 'btn btn-success';
                registeredBtn.disabled = true;
                registeredBtn.innerHTML = '<span>✓ Registered</span>';
                
                // Create Cancel button
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-warning';
                cancelBtn.id = 'cancel-btn';
                cancelBtn.innerHTML = '<span>Cancel Registration</span>';
                
                // Replace original button
                const form = document.getElementById('registration-form');
                if (form) {
                    form.innerHTML = '';
                    form.appendChild(registeredBtn);
                    form.appendChild(cancelBtn);
                    
                    // Re-add Back button
                    const backBtn = document.createElement('button');
                    backBtn.type = 'button';
                    backBtn.className = 'btn btn-secondary';
                    backBtn.id = 'backBtn';
                    backBtn.innerHTML = '<span>Back to Events</span>';
                    backBtn.addEventListener('click', function() {
                        window.location.href = 'event.php';
                    });
                    form.appendChild(backBtn);
                    
                    // Re-bind Cancel button event
                    cancelBtn.addEventListener('click', function() {
                        if (isSubmitting) return;
                        
                        showConfirm(
                            'cancel',
                            `Are you sure you want to cancel your registration? Your <?php echo $event['Points']; ?> points will be deducted.`,
                            'Cancel Registration'
                        );
                    });
                }
            }
        }
        
        // Sidebar interaction
        document.querySelectorAll('.nav-btn').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Mobile sidebar toggle
        let isSidebarOpen = false;
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
            isSidebarOpen = !isSidebarOpen;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // B key to go back
            if (e.key === 'b' || e.key === 'B') {
                e.preventDefault();
                document.getElementById('backBtn')?.click();
            }
            // Ctrl+H to go to homepage
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'homepage.php';
            }
            // Esc key to close sidebar (mobile)
            if (e.key === 'Escape' && window.innerWidth <= 600 && isSidebarOpen) {
                toggleSidebar();
            }
            // Esc key to close confirmation dialog
            if (e.key === 'Escape' && !quickConfirm.classList.contains('hidden')) {
                hideConfirm();
            }
        });
        
        // Page load animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>