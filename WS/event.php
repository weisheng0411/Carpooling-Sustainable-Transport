<?php
// Start session
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

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current user information
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT user_id, name, username, email, phone_number, 
                          gender, photo_url, current_point, summary_point, role 
                   FROM user_acc 
                   WHERE user_id = :user_id 
                   LIMIT 1";
    
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() > 0) {
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Check if user is an approved organizer
    $organizer_query = "SELECT Organizer_ID, Status 
                       FROM organizer 
                       WHERE User_ID = :user_id 
                       AND Status = 'Pass' 
                       LIMIT 1";
    
    $organizer_stmt = $conn->prepare($organizer_query);
    $organizer_stmt->bindParam(':user_id', $user_id);
    $organizer_stmt->execute();
    
    $is_approved_organizer = false;
    $current_organizer_id = null;
    
    if ($organizer_stmt->rowCount() > 0) {
        $is_approved_organizer = true;
        $organizer = $organizer_stmt->fetch(PDO::FETCH_ASSOC);
        $current_organizer_id = $organizer['Organizer_ID'];
    }
    
    // Get event data - modified query to show pending events to creator
    if ($is_approved_organizer && $current_organizer_id) {
        // Organizers can see their own pending events
        $events_query = "SELECT Event_ID, Name, Description, Start_Date, 
                                End_Date, Location, Points, Photo_URL, 
                                Max_member, Status, Organizer_ID
                         FROM event 
                         WHERE (Status != 'Completed' OR End_Date >= CURDATE())
                         AND (Status != 'Pending' OR Organizer_ID = :organizer_id)
                         ORDER BY 
                             CASE Status
                                 WHEN 'Active' THEN 1
                                 WHEN 'Upcoming' THEN 2
                                 WHEN 'Pending' THEN 3
                                 ELSE 4
                             END,
                             Start_Date ASC";
        
        $events_stmt = $conn->prepare($events_query);
        $events_stmt->bindParam(':organizer_id', $current_organizer_id, PDO::PARAM_INT);
    } else {
        // Regular users cannot view pending events
        $events_query = "SELECT Event_ID, Name, Description, Start_Date, 
                                End_Date, Location, Points, Photo_URL, 
                                Max_member, Status, Organizer_ID
                         FROM event 
                         WHERE (Status != 'Completed' OR End_Date >= CURDATE())
                         AND Status != 'Pending'
                         ORDER BY 
                             CASE Status
                                 WHEN 'Active' THEN 1
                                 WHEN 'Upcoming' THEN 2
                                 ELSE 3
                             END,
                             Start_Date ASC";
        
        $events_stmt = $conn->prepare($events_query);
    }
    
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get events user has registered for
    $registered_query = "SELECT ed.Event_ID 
                         FROM event_details ed
                         JOIN registration r ON ed.Reg_ID = r.Reg_ID
                         WHERE r.User_ID = :user_id";
    
    $registered_stmt = $conn->prepare($registered_query);
    $registered_stmt->bindParam(':user_id', $user_id);
    $registered_stmt->execute();
    $registered_events = $registered_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Get notification count
    $notif_query = "SELECT COUNT(*) as total_count FROM notification";
    $notif_stmt = $conn->prepare($notif_query);
    $notif_stmt->execute();
    $notification = $notif_stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $notification['total_count'] ?? 0;
    
} catch(PDOException $e) {
    error_log("Database error in event.php: " . $e->getMessage());
    $db_error = true;
    $events = [];
    $registered_events = [];
    $is_approved_organizer = false;
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - EcoCommute</title>
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

        /* Search Box */
        .search-box {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 14px 20px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .filter-btn {
            padding: 14px 24px;
            background: var(--eco-green);
            color: white;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            background: white;
            border-radius: 30px;
            font-weight: 500;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            border-color: var(--light-green);
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: var(--eco-green);
            color: white;
            border-color: var(--eco-green);
        }

        .create-event-btn {
            margin-left: auto;
            padding: 12px 24px;
            background: var(--light-green);
            color: white;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .create-event-btn:hover {
            background: var(--eco-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
        }

        /* Event Grid */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        
        .event-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.15);
            border-color: var(--light-green);
        }

        .event-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #e0f2f1, #c8e6c9);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .event-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            line-height: 1.3;
            flex: 1;
        }

        .event-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            white-space: nowrap;
        }

        .status-active {
            background-color: #e8f5e9;
            color: var(--eco-green);
        }

        .status-upcoming {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .status-joined {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .status-pending {
            background-color: #f5f5f5;
            color: #757575;
        }

        .event-description {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .event-details {
            background-color: #f8f9f8;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-icon {
            color: var(--light-green);
            width: 16px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .event-points {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .view-btn {
            padding: 10px 20px;
            background: var(--eco-green);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .view-btn:hover {
            background: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
        }

        /* No Events Message */
        .no-events {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
            font-size: 16px;
            grid-column: 1 / -1;
        }

        /* Database Error Message */
        .db-error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #c33;
            text-align: center;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            padding: 10px 14px;
            background: var(--eco-green);
            color: white;
            font-size: 20px;
            border-radius: 8px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 25px;
            }
            
            .event-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            
            .event-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .tabs {
                gap: 10px;
            }
            
            .tab-btn, .create-event-btn {
                padding: 10px 18px;
                font-size: 14px;
            }
        }

        @media (max-width: 600px) {
            .main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                padding-top: 70px;
            }
            
            .mobile-menu-btn {
                display: block;
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
            
            .search-box {
                flex-direction: column;
                gap: 10px;
            }
            
            .tabs {
                flex-direction: column;
                gap: 10px;
            }
            
            .create-event-btn {
                margin-left: 0;
                margin-top: 10px;
            }
            
            .event-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <div class="mobile-menu-btn" onclick="toggleSidebar()">☰</div>

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
                    <h2>Events</h2>
                    <p>Discover and join exciting eco-friendly events! 🌿</p>
                </div>
            </div>
           
            <hr>
            
            <!-- Database Error Message -->
            <?php if (isset($db_error) && $db_error): ?>
            <div class="db-error">
                <strong>Database Connection Error</strong>
                <p>Unable to load events. Please try again later.</p>
            </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search events by name, location or description..." id="searchInput">
                <button class="filter-btn" onclick="applyFilters()">
                    <span>🔍</span>
                    <span>Search</span>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="filterEvents('all')">All Events</button>
                <button class="tab-btn" onclick="filterEvents('active')">Active</button>
                <button class="tab-btn" onclick="filterEvents('joined')">Joined</button>
                <button class="tab-btn" onclick="filterEvents('upcoming')">Upcoming</button>
                <?php if ($is_approved_organizer): ?>
                <button class="tab-btn" onclick="filterEvents('pending')">Pending</button>
                <?php endif; ?>
                <?php if ($is_approved_organizer): ?>
                <button class="create-event-btn" onclick="window.location.href='create_event.php'">
                    <span>+</span>
                    <span>Create Event</span>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Event Card Grid -->
            <div class="event-grid" id="eventGrid">
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): 
                        // Check if user is registered for this event
                        $is_registered = in_array($event['Event_ID'], $registered_events);
                        
                        // Format dates
                        $start_date = date('M d, Y', strtotime($event['Start_Date']));
                        $end_date = date('M d, Y', strtotime($event['End_Date']));
                        
                        // Set status color and text
                        $status_text = '';
                        $status_class = '';
                        
                        if ($is_registered) {
                            $status_class = 'status-joined';
                            $status_text = 'Joined ✓';
                        } else {
                            // Show based on database status
                            $db_status = strtolower($event['Status']);
                            switch ($db_status) {
                                case 'active':
                                    $status_class = 'status-active';
                                    $status_text = 'Active';
                                    break;
                                case 'upcoming':
                                    $status_class = 'status-upcoming';
                                    $status_text = 'Upcoming';
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending';
                                    $status_text = 'Pending';
                                    break;
                                default:
                                    $status_class = 'status-active';
                                    $status_text = 'Active';
                                    break;
                            }
                        }
                    ?>
                    <a href="event_detail.php?id=<?php echo $event['Event_ID']; ?>" class="event-card">
                        <div class="event-image">
                            <?php if (!empty($event['Photo_URL'])): ?>
                                <img src="<?php echo htmlspecialchars($event['Photo_URL']); ?>" alt="<?php echo htmlspecialchars($event['Name']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-medium); font-size: 18px;">
                                    🌱 Eco Event
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="event-content">
                            <div class="event-header">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['Name']); ?></h3>
                                <span class="event-status <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                            <p class="event-description">
                                <?php echo htmlspecialchars(substr($event['Description'], 0, 120)); ?>...
                            </p>
                            <div class="event-details">
                                <div class="detail-item">
                                    <span class="detail-icon">📅</span>
                                    <span><?php echo $start_date; ?> - <?php echo $end_date; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📍</span>
                                    <span><?php echo htmlspecialchars($event['Location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">👥</span>
                                    <span>Max: <?php echo $event['Max_member']; ?> participants</span>
                                </div>
                            </div>
                            <div class="event-footer">
                                <div class="event-points">
                                    <span>+<?php echo $event['Points']; ?></span>
                                    <span>Points</span>
                                </div>
                                <span class="view-btn">View Details</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">
                        <h3>No events available at the moment</h3>
                        <p>Check back soon for upcoming eco-friendly events!</p>
                        <?php if ($is_approved_organizer): ?>
                        <p style="margin-top: 15px;">
                            <button class="create-event-btn" style="padding: 10px 20px; font-size: 14px;" onclick="window.location.href='create_event.php'">
                                <span>+</span>
                                <span>Create Your First Event</span>
                            </button>
                        </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Sidebar navigation interaction
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

        // Tab interaction
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(tab => {
                    tab.classList.remove('active');
                });
                // Add active class to current tab
                this.classList.add('active');
            });
        });

        // Search and filter function
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase();
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const title = card.querySelector('.event-title').textContent.toLowerCase();
                const description = card.querySelector('.event-description').textContent.toLowerCase();
                const location = card.querySelector('.detail-item:nth-child(2) span:nth-child(2)').textContent.toLowerCase();
                const status = card.querySelector('.event-status').textContent.toLowerCase();
                
                // Check search term
                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) || 
                    description.includes(searchTerm) || 
                    location.includes(searchTerm);
                
                // Check tab filter
                let matchesTab = true;
                if (activeTab.includes('joined')) {
                    matchesTab = status.includes('joined');
                } else if (activeTab.includes('upcoming')) {
                    matchesTab = status.includes('upcoming');
                } else if (activeTab.includes('active')) {
                    matchesTab = status.includes('active');
                } else if (activeTab.includes('pending')) {
                    matchesTab = status.includes('pending');
                }
                // 'all' tab shows all events
                
                // Show or hide card
                if (matchesSearch && matchesTab) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Filter events by tab
        function filterEvents(filter) {
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const status = card.querySelector('.event-status').textContent.toLowerCase();
                
                switch(filter) {
                    case 'all':
                        card.style.display = 'flex';
                        break;
                    case 'joined':
                        card.style.display = status.includes('joined') ? 'flex' : 'none';
                        break;
                    case 'active':
                        card.style.display = status.includes('active') ? 'flex' : 'none';
                        break;
                    case 'upcoming':
                        card.style.display = status.includes('upcoming') ? 'flex' : 'none';
                        break;
                    case 'pending':
                        card.style.display = status.includes('pending') ? 'flex' : 'none';
                        break;
                }
            });
        }

        // Search box enter key support
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+F to focus search box
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // Ctrl+H to go to homepage
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'homepage.php';
            }
            // Esc key to close sidebar (mobile)
            if (e.key === 'Escape' && window.innerWidth <= 600) {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
            // Ctrl+E to create event (only for approved organizers)
            <?php if ($is_approved_organizer): ?>
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = 'create_event.php';
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>