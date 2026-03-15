<?php
// 启动session
session_start();

$host = 'localhost';
$dbname = 'wdd';        
$username = 'root';
$password = '';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // 连接数据库
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 获取当前用户信息
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT user_id, apu_id, name, username, email, phone_number, 
                          gender, photo_url, summary_point, current_point, role 
                   FROM user_acc 
                   WHERE user_id = :user_id 
                   LIMIT 1";
    
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() > 0) {
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // 检查用户是否为管理员
        $is_admin = (strtolower($user['role']) === 'admin');
        
        // 计算CO₂节省量（假设每100点=1kg CO2）
        $co2_saved = floor($user['current_point'] / 100);
        
        // 获取挑战数据
        $challenges_query = "SELECT Challenges_ID, Name, Description, Point, 
                            Type, Published_date, Photo_URL
                            FROM challenges 
                            ORDER BY Published_date DESC 
                            LIMIT 3";
        
        $challenges_stmt = $conn->prepare($challenges_query);
        $challenges_stmt->execute();
        $challenges = $challenges_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取事件数据 - 修改查询以包含正确的状态显示
        // 注意：这里只显示active和upcoming事件，pending事件只对创建者可见（在事件详情页处理）
        $events_query = "SELECT e.Event_ID, e.Name, e.Description, e.Start_Date, 
                        e.End_Date, e.Location, e.Points, e.Photo_URL, e.Status,
                        e.Organizer_ID
                        FROM event e
                        WHERE e.Status IN ('Active', 'Upcoming')
                        AND (e.Status != 'Pending' OR e.Organizer_ID = :user_id)
                        ORDER BY 
                            CASE e.Status
                                WHEN 'Active' THEN 1
                                WHEN 'Upcoming' THEN 2
                                ELSE 3
                            END,
                            e.Start_Date DESC 
                        LIMIT 2";
        
        $events_stmt = $conn->prepare($events_query);
        $events_stmt->bindParam(':user_id', $user_id);
        $events_stmt->execute();
        $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取新闻数据
        $news_query = "SELECT news_id, title, content, image, date
                      FROM news 
                      ORDER BY date DESC 
                      LIMIT 2";
        
        $news_stmt = $conn->prepare($news_query);
        $news_stmt->execute();
        $news = $news_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // count unread
        $unread_count = 0;
        $sqlBadge = "SELECT COUNT(*) as unread_count 
                    FROM user_notification 
                    WHERE user_ID = :user_id 
                    AND is_read = 0";
        $badge_stmt = $conn->prepare($sqlBadge);
        $badge_stmt->bindParam(':user_id', $user_id);
        $badge_stmt->execute();
        $rowBadge = $badge_stmt->fetch(PDO::FETCH_ASSOC);
        $unread_count = $rowBadge['unread_count'];


        //avatar
        $avatar_stmt = $conn->prepare("SELECT photo_url FROM user_acc WHERE user_id = :user_id");
        $avatar_stmt->bindParam(':user_id', $user_id);
        $avatar_stmt->execute();
        $row = $avatar_stmt->fetch(PDO::FETCH_ASSOC);

        
    } else {
        // 用户不存在，注销并重定向
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
} catch(PDOException $e) {
    // 记录错误但不显示给用户
    error_log("Database error in homepage: " . $e->getMessage());
    $db_error = true;
    $challenges = [];
    $events = [];
    $news = [];
    $unread_count = 0;
    $is_admin = false;
    
    // 如果发生错误，设置默认用户数据
    if (!isset($user)) {
        $user = [
            'name' => $_SESSION['name'] ?? 'Guest',
            'username' => $_SESSION['username'] ?? 'User',
            'current_point' => $_SESSION['current_point'] ?? 0,
            'summary_point' => $_SESSION['summary_point'] ?? 0,
            'role' => $_SESSION['role'] ?? 'User',
            'photo_url' => $_SESSION['photo_url'] ?? null
        ];
        $co2_saved = floor(($user['current_point'] ?? 0) / 100);
        $is_admin = (strtolower($user['role']) === 'admin');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - EcoCommute</title>
    <style>
        /* 保持原有的CSS样式不变 */
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* ========== 侧边栏设计 ========== */
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-icon:hover {
            transform: rotate(15deg) scale(1.1);
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
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .nav-btn:hover::before {
            left: 100%;
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

        /* ========== 主内容区 ========== */
        .main {
            margin-left: 300px;
            padding: 30px;
            width: calc(100% - 300px);
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
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .icon-btn {
            position: relative;
            background: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: var(--eco-green);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .icon-btn:hover {
            background: var(--light-green);
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 16px rgba(46, 125, 50, 0.2);
        }
        
        /* 管理员按钮样式 - 与整体设计一致 */
        .admin-icon-btn {
            position: relative;
            background: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
            color: var(--admin-blue);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }

        .admin-icon-btn:hover {
            background: var(--admin-blue);
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
            border-color: var(--admin-blue-light);
        }

        .admin-icon-btn:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);
        }

        /* 管理员图标 */
        .admin-icon-btn svg {
            width: 22px;
            height: 22px;
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.1));
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff6b6b;
            color: white;
            width: 22px;
            height: 22px;
            font-size: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .avatar-btn {
            background: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
            box-shadow: var(--shadow);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .avatar-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-color: var(--light-green);
        }

        .avatar-img {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--light-green), transparent);
            margin: 20px 0 30px 0;
        }
        
        /* 主要区域容器 */
        .content-sections {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        /* 通用区域样式 */
        .section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--light-green);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 700;
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
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }
        
        /* Challenge 部分样式 */
        .challenge-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        
        .challenge-card {
            background-color: #f8f9f8;
            border: 2px solid #c8e6c9;
            border-radius: 12px;
            padding: 20px;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .challenge-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--light-green), var(--accent-green));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .challenge-card:hover::before {
            transform: scaleX(1);
        }

        .challenge-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--light-green);
        }
        
        .challenge-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, white, #f5f5f5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: var(--eco-green);
            font-size: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .challenge-card:hover .challenge-icon {
            transform: rotate(10deg) scale(1.1);
        }
        
        .challenge-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        
        .challenge-desc {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 15px;
            flex-grow: 1;
            line-height: 1.5;
        }
        
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-label {
            font-size: 13px;
            color: var(--text-medium);
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--light-green), var(--accent-green));
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .continue-btn {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .continue-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .continue-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .continue-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
        }

        .continue-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
        }
        
        /* Event 部分样式 */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .event-card {
            background: linear-gradient(135deg, #f0f7ff, #e3f2fd);
            border: 2px solid #bbdefb;
            border-radius: 12px;
            padding: 20px;
            min-height: 150px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .event-card:hover::before {
            transform: translateX(100%);
        }

        .event-card:hover {
            transform: translateX(8px);
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.15);
        }
        
        .event-card h4 {
            margin-top: 0;
            color: var(--text-dark);
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .event-card p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .view-event-btn {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .view-event-btn:hover {
            background: linear-gradient(135deg, #43a047, #2e7d32);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
        }
        
        /* 事件状态标签 */
        .event-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border: 2px solid transparent;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .event-card:hover .event-status {
            transform: scale(1.05);
        }
        
        .status-active {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: var(--eco-green);
            border-color: #a5d6a7;
        }
        
        .status-joined {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            border-color: #90caf9;
        }
        
        .status-upcoming {
            background: linear-gradient(135deg, #fff3e0, #ffcc80);
            color: #ef6c00;
            border-color: #ffb74d;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            color: #757575;
            border: 1px dashed #bdbdbd;
        }
        
        .status-past {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            color: #9e9e9e;
            border-color: #bdbdbd;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            border-color: #ef9a9a;
        }
        
        /* 新的News和Register容器 */
        .news-register-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        /* News 部分样式 */
        .news-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .news-card {
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            border-left: 4px solid var(--eco-green);
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .news-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--eco-green);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
        }

        .news-card:hover::before {
            transform: scaleY(1);
        }

        .news-card:hover {
            background: linear-gradient(135deg, #fff3cd, #ffe082);
            transform: translateX(8px);
            box-shadow: 0 8px 16px rgba(255, 152, 0, 0.1);
        }
        
        .news-card h4 {
            margin-top: 0;
            color: var(--text-dark);
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .news-card p {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .view-btn {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            background: linear-gradient(135deg, #43a047, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(46, 125, 50, 0.3);
        }
        
        /* Register Event Organizer 部分样式 */
        .register-section {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            border: 2px solid var(--eco-green);
            border-radius: var(--border-radius);
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            justify-content: center;
            min-height: 300px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
        }

        .register-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.2);
            background: linear-gradient(135deg, #dcedc8, #aed581);
        }
        
        .register-section h3 {
            font-size: 22px;
            margin-top: 0;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        .register-section p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .register-btn {
            background: linear-gradient(135deg, var(--light-green), var(--eco-green));
            color: white;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .register-btn::after {
            content: '→';
            margin-left: 8px;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }

        .register-btn:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .register-btn:hover {
            background: linear-gradient(135deg, #43a047, #2e7d32);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.3);
        }

        /* 数据库错误提示 */
        .db-error {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f44336;
            text-align: center;
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.1);
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                padding-top: 70px;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 侧边栏设计 -->
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
                    <a href="homepage.php" class="nav-btn active" id="homepage-btn">
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
        
        <main class="main">
            
            <div class="top-bar">
                <div class="page-title">
                    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                    <p>Have a green day! 🌱 You've saved <?php echo $co2_saved; ?>kg CO₂ this month</p>
                </div>  
                
                <div class="top-right">
                    <!-- Notification -->
                    <a href="../RY/notification_page.php" class="icon-btn">
                    <span style="font-size:20px">🔔</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge">
                            <span class="red-dot"><?php echo $unread_count; ?></span>
                        </span>
                    <?php endif; ?>
                    </a>
                    
                    <!-- Admin Panel Button (only for admin) -->
                    <?php if ($is_admin): ?>
                    <a href="../LK/AdminPanel.php" class="admin-icon-btn" title="Admin Panel">
                         <span class="icon">👤</span>
                    </a>
                    <?php endif; ?>

                    <!-- Avatar -->
                    <a href="../Aston/profile.php" class="avatar-btn">
                        <img 
                            src="/ASS-WDD/<?php echo htmlspecialchars($row['photo_url']); ?>"
                            class="avatar-img"
                            alt="Avatar"
                        >
                    </a>
                </div>
            </div>
           
            <hr>
            
            <!-- 数据库错误提示 -->
            <?php if (isset($db_error) && $db_error): ?>
            <div class="db-error">
                <strong>Database Connection Error</strong>
                <p>Unable to load dynamic data. Please try again later or contact support.</p>
            </div>
            <?php endif; ?>
            
            <!-- Main Content Sections -->
            <div class="content-sections">
                <!-- Challenge Section -->
                <section class="section challenge-section">
                    <div class="section-header">
                        <h3 class="section-title">Challenge</h3>
                        <a href="../Aston/Chlng.php" class="continue-btn">Continue Challenge</a>
                    </div>
                    
                    <?php if (isset($challenges) && count($challenges) > 0): ?>
                    <div class="challenge-grid">
                        <?php foreach ($challenges as $challenge): 
                            // 设置挑战图标
                            $icons = [
                                'cycling' => '🚴',
                                'driving' => '🚗',
                                'public' => '🚌',
                                'walking' => '🚶',
                                'eco' => '🌿',
                                'recycle' => '♻️'
                            ];
                            $type = strtolower($challenge['Type']);
                            $icon = $icons[$type] ?? $icons['eco'];
                            
                            // 格式化日期
                            $published_date = date('M d, Y', strtotime($challenge['Published_date']));
                        ?>
                        <a href="../Aston/Chlng.php?id=<?php echo $challenge['Challenges_ID']; ?>" class="challenge-card">
                            <div class="challenge-icon"><?php echo $icon; ?></div>
                            <h4 class="challenge-name"><?php echo htmlspecialchars($challenge['Name']); ?></h4>
                            <p class="challenge-desc"><?php echo htmlspecialchars(substr($challenge['Description'], 0, 100) . '...'); ?></p>
                            <div class="progress-container">
                                <div class="progress-label">Reward: <?php echo $challenge['Point']; ?> points</div>
                                <div class="progress-label">Published: <?php echo $published_date; ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p>No challenges available. <a href="../Aston/Chlng.php">Check back soon!</a></p>
                    <?php endif; ?>
                </section>
                
                <!-- Event Section -->
                <section class="section event-section">
                    <div class="section-header">
                        <h3 class="section-title">Event</h3>
                        <a href="event.php" class="view-event-btn">View Event Detail</a>
                    </div>
                    
                    <?php if (isset($events) && count($events) > 0): ?>
                    <div class="event-grid">
                        <?php foreach ($events as $event): 
                            
                            $start_date = date('M d, Y', strtotime($event['Start_Date']));
                            $end_date = date('M d, Y', strtotime($event['End_Date']));
                            
                            
                            $status_class = '';
                            $status_text = '';
                            
                            
                            $db_status = strtolower($event['Status']);
                            
                            
                            $is_my_pending = ($db_status === 'pending' && $event['Organizer_ID'] == $user_id);
                            
                            if ($db_status === 'active') {
                                $status_class = 'status-active';
                                $status_text = 'Active';
                            } elseif ($db_status === 'upcoming') {
                                $status_class = 'status-upcoming';
                                $status_text = 'Upcoming';
                            } elseif ($is_my_pending) {
                                $status_class = 'status-pending';
                                $status_text = 'Pending';
                            } else {
                               
                                continue; 
                            }
                        ?>
                        <a href="event_detail.php?id=<?php echo $event['Event_ID']; ?>" class="event-card">
                            <div class="event-status <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($event['Name']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($event['Description'], 0, 150) . '...'); ?></p>
                            <small style="color: #666;">
                                <strong>Date:</strong> <?php echo $start_date; ?> - <?php echo $end_date; ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['Location']); ?><br>
                                <strong>Points:</strong> <?php echo $event['Points']; ?>
                            </small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p>No events available at the moment.</p>
                    <?php endif; ?>
                </section>
                
               
                <div class="news-register-container">
                    <!-- News Section -->
                    <section class="section news-section">
                        <div class="section-header">
                            <h3 class="section-title">News</h3>
                        </div>
                        
                        <?php if (isset($news) && count($news) > 0): ?>
                        <div class="news-grid">
                            <?php foreach ($news as $news_item): 
                                $news_date = date('M d, Y', strtotime($news_item['date']));
                            ?>
                            <a href="../RY/news_page.php?id=<?php echo $news_item['news_id']; ?>" class="news-card">
                                <h4><?php echo htmlspecialchars($news_item['title']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($news_item['content'], 0, 150) . '...'); ?></p>
                                <small style="color: #666;">Published: <?php echo $news_date; ?></small>
                                <span class="view-btn">View</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p>No news available at the moment.</p>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Register Event Organizer Section -->
                    <section class="register-section">
                        <h3>Register Event Organizer</h3>
                        <p>Organize your own events to promote sustainable transportation in your community. Join us as an event organizer and make a difference!</p>
                        <a href="register_event_organizer.php" class="register-btn">Register Now</a>
                    </section>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        
        document.querySelectorAll('.nav-btn').forEach(link => {
            link.addEventListener('click', function() {
                
                document.querySelectorAll('.nav-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                this.classList.add('active');
            });
        });

        
        function updateEcoStats() {
           
            console.log('Updating eco stats from database...');
        }

        
        setInterval(updateEcoStats, 60000);

        
        console.log('EcoCommute homepage loaded for user ID: <?php echo $user_id; ?>');
        
        
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
            
            
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, 100 + (index * 100));
            });
        });

       
        let isSidebarOpen = false;
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
            isSidebarOpen = !isSidebarOpen;
        }

        
        document.addEventListener('keydown', function(e) {
            // Ctrl+A 
            <?php if ($is_admin): ?>
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                window.location.href = 'admin_panel.php';
            }
            <?php endif; ?>
            
            // Ctrl+H 
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                document.querySelector('#homepage-btn').click();
            }
            
            if (e.key === 'Escape' && window.innerWidth <= 600 && isSidebarOpen) {
                toggleSidebar();
            }
        });
        
        
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>