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

// Initialize variables
$db_error = false;
$user = null;
$items = [];
$redeem_records = [];
$unread_count = 0;

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
    
    // Get all redeemable items
    $items_query = "SELECT Items_ID, Name, Points_Required, Stock 
                    FROM items 
                    WHERE Stock > 0 
                    ORDER BY Points_Required ASC";
    
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->execute();
    $items = $items_stmt->fetchAll();
    
    // Get user's redemption records
    $records_query = "SELECT r.RR_ID, r.User_ID, r.Items_ID, r.Points_spent, 
                             r.Quantity, r.Date, i.Name as item_name
                      FROM redeem_record r
                      JOIN items i ON r.Items_ID = i.Items_ID
                      WHERE r.User_ID = :user_id 
                      ORDER BY r.Date DESC 
                      LIMIT 10";
    
    $records_stmt = $conn->prepare($records_query);
    $records_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $records_stmt->execute();
    $redeem_records = $records_stmt->fetchAll();
    
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
    
} catch(PDOException $e) {
    error_log("Main database error in reward_page.php: " . $e->getMessage());
    $db_error = true;
}

// Handle item redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !$db_error) {
    try {
        if ($_POST['action'] === 'redeem' && isset($_POST['item_id'])) {
            $item_id = intval($_POST['item_id']);
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            // Check if item exists and has stock
            $check_item_query = "SELECT Points_Required, Stock FROM items WHERE Items_ID = :item_id";
            $check_item_stmt = $conn->prepare($check_item_query);
            $check_item_stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $check_item_stmt->execute();
            
            if ($check_item_stmt->rowCount() > 0) {
                $item = $check_item_stmt->fetch();
                $points_required = $item['Points_Required'] * $quantity;
                
                // Check if user has enough points
                if ($user['current_point'] >= $points_required && $item['Stock'] >= $quantity) {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Update user points (only deduct current_point, summary_point remains unchanged)
                    $update_points_query = "UPDATE user_acc 
                                           SET current_point = current_point - :points 
                                           WHERE user_id = :user_id";
                    $update_points_stmt = $conn->prepare($update_points_query);
                    $update_points_stmt->bindParam(':points', $points_required, PDO::PARAM_INT);
                    $update_points_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $update_points_stmt->execute();
                    
                    // Update item stock
                    $update_stock_query = "UPDATE items 
                                          SET Stock = Stock - :quantity 
                                          WHERE Items_ID = :item_id";
                    $update_stock_stmt = $conn->prepare($update_stock_query);
                    $update_stock_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                    $update_stock_stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                    $update_stock_stmt->execute();
                    
                    // Create redemption record
                    $insert_record_query = "INSERT INTO redeem_record (User_ID, Items_ID, Points_spent, Quantity) 
                                           VALUES (:user_id, :item_id, :points_spent, :quantity)";
                    $insert_record_stmt = $conn->prepare($insert_record_query);
                    $insert_record_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $insert_record_stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                    $insert_record_stmt->bindParam(':points_spent', $points_required, PDO::PARAM_INT);
                    $insert_record_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                    $insert_record_stmt->execute();
                    
                    $conn->commit();
                    
                    // Get item name
                    $item_name_query = "SELECT Name FROM items WHERE Items_ID = :item_id";
                    $item_name_stmt = $conn->prepare($item_name_query);
                    $item_name_stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                    $item_name_stmt->execute();
                    $item_name_data = $item_name_stmt->fetch();
                    
                    // Set success message
                    $_SESSION['redeem_success'] = [
                        'success' => true,
                        'item_name' => $item_name_data['Name'] ?? 'Item',
                        'points_spent' => $points_required,
                        'date' => date('F j, Y'),
                        'code' => 'RV-' . date('Y') . '-' . rand(100000, 999999)
                    ];
                    
                    // Refresh page
                    header("Location: reward_page.php");
                    exit();
                } else {
                    $_SESSION['message'] = "Insufficient points or item out of stock!";
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = "Item not found!";
                $_SESSION['message_type'] = 'error';
            }
        }
    } catch(PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Redeem error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred. Please try again.";
        $_SESSION['message_type'] = 'error';
    }
}

// Check for successful redemption message
$redeem_success = null;
if (isset($_SESSION['redeem_success'])) {
    $redeem_success = $_SESSION['redeem_success'];
    unset($_SESSION['redeem_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Exchange - EcoCommute</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
        }

        body {
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
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
            max-width: 1400px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
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
        
             /* ========== Reward Exchange Page Styles ========== */
        .reward-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        .reward-main-content {
            flex: 3;
        }
        
        .reward-sidebar {
            flex: 1;
            min-width: 300px;
        }
        
        
        .user-points-card {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        
        .user-points-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-points-header h3 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }
        
        .points-display {
            font-size: 42px;
            font-weight: 800;
            text-align: center;
            margin: 20px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .points-summary {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .points-item {
            text-align: center;
        }
        
        .points-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .points-value {
            font-size: 18px;
            font-weight: 700;
        }
        
        /* Rewards Grid */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .reward-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid #e0e0e0;
        }
        
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
        }
        
        .reward-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .reward-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .reward-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .reward-info h3 {
            font-size: 18px;
            color: var(--text-dark);
            margin: 0 0 5px 0;
            font-weight: 700;
        }
        
        .reward-info p {
            color: var(--text-light);
            font-size: 14px;
            margin: 0;
        }
        
        .reward-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .reward-points {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .reward-stock {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .redeem-btn {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .redeem-btn:hover {
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }
        
        .redeem-btn:disabled {
            background: #e0e0e0;
            color: #9e9e9e;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Redemption History */
        .history-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .history-header h3 {
            font-size: 18px;
            color: var(--text-dark);
            margin: 0;
            font-weight: 700;
        }
        
        .history-list {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .history-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .history-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .history-list::-webkit-scrollbar-thumb {
            background: #c8e6c9;
            border-radius: 10px;
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }
        
        .history-item:hover {
            background-color: #f9f9f9;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-details h4 {
            font-size: 15px;
            color: var(--text-dark);
            margin: 0 0 5px 0;
        }
        
        .history-date {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .history-points {
            font-size: 18px;
            font-weight: 700;
            color: #e53935; /* Red indicates point deduction */
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
            padding: 5px;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background-color: #f5f5f5;
            border: 2px solid transparent;
            border-radius: 25px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-tab.active {
            background-color: var(--eco-green);
            color: white;
            border-color: var(--eco-green);
        }
        
        .filter-tab:hover:not(.active) {
            background-color: #e8f5e9;
            color: var(--text-dark);
            border-color: #c8e6c9;
        }
        
        /* Successful Redemption Popup */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin: 0 auto 30px;
        }
        
        .success-title {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        .success-message {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .redeemed-details {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            margin: 30px 0;
            text-align: left;
            border-left: 4px solid var(--eco-green);
        }
        
        .redeemed-details h4 {
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .redeemed-details p {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .redeem-code {
            background-color: #e8f5e9;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 16px;
            font-weight: 600;
            color: var(--eco-green);
            margin-top: 15px;
            text-align: center;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .modal-btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--eco-green) 100%);
            color: white;
            border: none;
        }
        
        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
            transform: translateY(-2px);
        }
        
        .modal-btn-secondary {
            background-color: #f5f5f5;
            color: var(--text-dark);
            border: 1px solid #ddd;
        }
        
        .modal-btn-secondary:hover {
            background-color: #e8e8e8;
            transform: translateY(-2px);
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
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Loading Animation */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--eco-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Database Error Message */
        .error-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .error-card h3 {
            color: #c62828;
            margin-bottom: 20px;
        }
        
        .error-card p {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 25px;
            }
            
            .reward-container {
                flex-direction: column;
            }
            
            .reward-sidebar {
                width: 100%;
                min-width: auto;
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
            
            .rewards-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
            
            .rewards-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .modal-btn {
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
                    <a href="event.php" class="nav-btn">
                        <span class="icon">📅</span>
                        <span>Event</span>
                    </a>
                    <a href="../Aston/Chlng.php" class="nav-btn">
                        <span class="icon">🏆</span>
                        <span>Challenge</span>
                    </a>
                    <a href="reward_page.php" class="nav-btn active">
                        <span class="icon">🎁</span>
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
                    <h2>Reward Exchange Center</h2>
                    <p>Redeem your green points for amazing rewards! 🌿</p>
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
            <?php if ($db_error): ?>
                <div class="error-card">
                    <h3>⚠️ Database Connection Error</h3>
                    <p>Unable to connect to the database. Please check your database configuration and try again.</p>
                    <p>Database: <strong><?php echo htmlspecialchars($dbname); ?></strong></p>
                    <a href="homepage.php" class="modal-btn modal-btn-primary" style="margin-top: 20px;">Return to Homepage</a>
                </div>
            <?php elseif (!$user): ?>
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            <?php else: ?>
            
            <!-- Reward Exchange Page Content -->
            <div class="reward-container">
                <!-- Left Main Content -->
                <div class="reward-main-content">
                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <div class="filter-tab active" data-filter="all">All Rewards</div>
                        <div class="filter-tab" data-filter="drinks">Drinks</div>
                        <div class="filter-tab" data-filter="eco">Eco Products</div>
                        <div class="filter-tab" data-filter="food">Food</div>
                        <div class="filter-tab" data-filter="voucher">Vouchers</div>
                        <div class="filter-tab" data-filter="gadgets">Gadgets</div>
                    </div>
                    
                    <!-- Rewards Grid -->
                    <div class="rewards-grid" id="rewardsGrid">
                        <?php if (empty($items)): ?>
                            <div class="error-card" style="grid-column: 1 / -1;">
                                <h3>No Rewards Available</h3>
                                <p>There are currently no rewards available for redemption. Please check back later.</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            // Determine category based on item name
                            function getItemCategory($itemName) {
                                $name = strtolower($itemName);
                                if (strpos($name, 'coffee') !== false || strpos($name, 'starbucks') !== false || strpos($name, 'tea') !== false) {
                                    return 'drinks';
                                } elseif (strpos($name, 'eco') !== false || strpos($name, 'reusable') !== false || strpos($name, 'bamboo') !== false) {
                                    return 'eco';
                                } elseif (strpos($name, 'voucher') !== false || strpos($name, 'gift') !== false) {
                                    return 'voucher';
                                } elseif (strpos($name, 'pizza') !== false || strpos($name, 'burger') !== false) {
                                    return 'food';
                                } elseif (strpos($name, 'earbuds') !== false || strpos($name, 'power bank') !== false) {
                                    return 'gadgets';
                                } else {
                                    return 'voucher';
                                }
                            }
                            
                            // Get icon
                            function getItemIcon($category) {
                                $icons = [
                                    'drinks' => 'fa-coffee',
                                    'eco' => 'fa-leaf',
                                    'food' => 'fa-utensils',
                                    'voucher' => 'fa-ticket-alt',
                                    'gadgets' => 'fa-mobile-alt'
                                ];
                                return $icons[$category] ?? 'fa-gift';
                            }
                            
                            foreach ($items as $item): 
                                $category = getItemCategory($item['Name']);
                                $icon = getItemIcon($category);
                                $can_redeem = $user['current_point'] >= $item['Points_Required'] && $item['Stock'] > 0;
                                $is_popular = $item['Stock'] < 5;
                            ?>
                                <div class="reward-card" data-category="<?php echo $category; ?>">
                                    <?php if ($is_popular): ?>
                                        <div class="reward-badge">Popular</div>
                                    <?php endif; ?>
                                    <div class="reward-header">
                                        <div class="reward-icon">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="reward-info">
                                            <h3><?php echo htmlspecialchars($item['Name']); ?></h3>
                                            <p><?php echo ucfirst($category); ?> Reward</p>
                                        </div>
                                    </div>
                                    
                                    <div class="reward-details">
                                        <div class="reward-points">
                                            <i class="fas fa-coins"></i>
                                            <span><?php echo $item['Points_Required']; ?> Points</span>
                                        </div>
                                        <div class="reward-stock">
                                            <i class="fas fa-box"></i>
                                            <span><?php echo $item['Stock']; ?> left</span>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" action="" class="redeem-form">
                                        <input type="hidden" name="action" value="redeem">
                                        <input type="hidden" name="item_id" value="<?php echo $item['Items_ID']; ?>">
                                        <button type="submit" class="redeem-btn" 
                                                <?php echo !$can_redeem ? 'disabled' : ''; ?>
                                                onclick="return confirm('Are you sure you want to redeem <?php echo htmlspecialchars($item['Name']); ?> for <?php echo $item['Points_Required']; ?> points? This will deduct from your current balance.');">
                                            <?php if ($can_redeem): ?>
                                                <i class="fas fa-gift"></i> Redeem Now
                                            <?php elseif ($item['Stock'] <= 0): ?>
                                                <i class="fas fa-times-circle"></i> Out of Stock
                                            <?php else: ?>
                                                <i class="fas fa-lock"></i> Need More Points
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Sidebar -->
                <div class="reward-sidebar">
                    <!-- User Points Card -->
                    <div class="user-points-card">
                        <div class="user-points-header">
                            <h3>Your Points Balance</h3>
                            <i class="fas fa-wallet" style="font-size: 24px;"></i>
                        </div>
                        <div class="points-display">
                            <?php echo number_format($user['current_point']); ?>
                        </div>
                        <div class="points-summary">
                            <div class="points-item">
                                <div class="points-value"><?php echo number_format($user['summary_point']); ?></div>
                                <div class="points-label">Total Earned</div>
                            </div>
                            <div class="points-item">
                                <div class="points-value"><?php echo number_format($user['current_point']); ?></div>
                                <div class="points-label">Available</div>
                            </div>
                            <div class="points-item">
                                <div class="points-value"><?php echo count($redeem_records); ?></div>
                                <div class="points-label">Items Redeemed</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Redemption History -->
                    <div class="history-card">
                        <div class="history-header">
                            <h3>Redeem History</h3>
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="history-list">
                            <?php if (empty($redeem_records)): ?>
                                <div style="text-align: center; padding: 40px 20px; color: var(--text-light);">
                                    <i class="fas fa-history" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <p>No redemption history yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($redeem_records as $record): ?>
                                    <div class="history-item">
                                        <div class="history-details">
                                            <h4><?php echo htmlspecialchars($record['item_name']); ?></h4>
                                            <div class="history-date">
                                                <?php echo date('M j, Y', strtotime($record['Date'])); ?>
                                            </div>
                                        </div>
                                        <div class="history-points">
                                            -<?php echo $record['Points_spent']; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Points Information -->
                    <div class="history-card">
                        <div class="history-header">
                            <h3>Points Information</h3>
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="history-list" style="display: flex; flex-direction: column; gap: 15px; padding: 10px;">
                            <div class="history-item" style="border: none; padding: 10px; background: #e8f5e9; border-radius: 8px;">
                                <div style="font-weight: 600; color: var(--eco-green);">Current Points</div>
                                <div style="font-size: 14px; color: var(--text-medium);">Your available balance for redeeming rewards. Can increase (from events) and decrease (from redemptions).</div>
                            </div>
                            <div class="history-item" style="border: none; padding: 10px; background: #e3f2fd; border-radius: 8px;">
                                <div style="font-weight: 600; color: #1976d2;">Total Earned Points</div>
                                <div style="font-size: 14px; color: var(--text-medium);">Your lifetime points earned from all events. This number only increases and never decreases.</div>
                            </div>
                            <div class="history-item" style="border: none; padding: 10px; background: #fff3e0; border-radius: 8px;">
                                <div style="font-weight: 600; color: #f57c00;">How to Earn Points</div>
                                <div style="font-size: 14px; color: var(--text-medium);">Join events and challenges to earn points for both Current and Total balances.</div>
                            </div>
                            <div class="history-item" style="border: none; padding: 10px; background: #fce4ec; border-radius: 8px;">
                                <div style="font-weight: 600; color: #c2185b;">Points Usage</div>
                                <div style="font-size: 14px; color: var(--text-medium);">Use Current Points to redeem rewards. Total Points remain unchanged.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Successful Redemption Popup -->
    <?php if ($redeem_success && $redeem_success['success']): ?>
    <div class="success-modal" id="successModal" style="display: flex;">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="success-title">Redemption Successful!</h2>
            <p class="success-message">Congratulations! Your reward has been redeemed successfully.</p>
            
            <div class="redeemed-details">
                <h4>Redeemed Item Details</h4>
                <p><i class="fas fa-gift"></i> <?php echo htmlspecialchars($redeem_success['item_name']); ?></p>
                <p><i class="fas fa-coins"></i> <?php echo number_format($redeem_success['points_spent']); ?> Points deducted</p>
                <p><i class="far fa-calendar"></i> <?php echo htmlspecialchars($redeem_success['date']); ?></p>
                <div class="redeem-code">
                    Code: <?php echo htmlspecialchars($redeem_success['code']); ?>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-primary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh Page
                </button>
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeSuccessModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Sidebar interaction
        document.querySelectorAll('.nav-btn').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Filter tab interaction
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove all active classes
                document.querySelectorAll('.filter-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Get filter value
                const filterValue = this.getAttribute('data-filter');
                
                // Get all reward cards
                const rewardCards = document.querySelectorAll('.reward-card');
                
                // Show/hide cards
                rewardCards.forEach(card => {
                    if (filterValue === 'all') {
                        card.style.display = 'block';
                    } else {
                        const cardCategory = card.getAttribute('data-category');
                        if (cardCategory === filterValue) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });

        // Close success popup
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.style.display = 'none';
                window.location.reload();
            }
        }

        // If page has success popup, close when clicking background
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('successModal');
            if (modal && modal.style.display === 'flex' && event.target === modal) {
                closeSuccessModal();
            }
        });

        // Close popup with ESC key
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('successModal');
            if (modal && modal.style.display === 'flex' && event.key === 'Escape') {
                closeSuccessModal();
            }
            
            // Ctrl+R to refresh page
            if (event.ctrlKey && event.key === 'r') {
                event.preventDefault();
                window.location.reload();
            }
            
            // B key to go back to homepage
            if (event.key === 'b' || event.key === 'B') {
                event.preventDefault();
                window.location.href = 'homepage.php';
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

        // Auto-dismiss message notifications
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });

        // Redemption button confirmation
        document.querySelectorAll('.redeem-btn:not([disabled])').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to redeem this item?')) {
                    e.preventDefault();
                }
            });
        });

        // Mobile sidebar toggle
        let isSidebarOpen = false;
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
            isSidebarOpen = !isSidebarOpen;
        }

        // Add hamburger menu for mobile (if needed)
        if (window.innerWidth <= 600) {
            const topBar = document.querySelector('.top-bar');
            const hamburgerBtn = document.createElement('button');
            hamburgerBtn.innerHTML = '☰';
            hamburgerBtn.style.cssText = `
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 101;
                background: var(--eco-green);
                color: white;
                border: none;
                border-radius: 5px;
                width: 40px;
                height: 40px;
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            hamburgerBtn.addEventListener('click', toggleSidebar);
            document.body.appendChild(hamburgerBtn);
        }
    </script>
</body>
</html>