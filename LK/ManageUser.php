<?php
session_start();
include("conn.php");

/* =========================
   DELETE USER
========================= */
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    mysqli_query($con, "DELETE FROM user_acc WHERE user_id = $delete_id");
    header("Location: ManageUser.php");
    exit();
}

/* =========================
   SEARCH
========================= */
$keyword = "";
if (isset($_GET['search'])) {
    $keyword = mysqli_real_escape_string($con, $_GET['search']);
}

/* =========================
   PAGINATION
========================= */
$records_per_page = 9; // 每页显示10个用户
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// 计算总用户数
$count_sql = "SELECT COUNT(*) as total FROM user_acc";
if ($keyword != "") {
    $count_sql .= " WHERE name LIKE '%$keyword%'
        OR username LIKE '%$keyword%'
        OR email LIKE '%$keyword%'
        OR apu_id LIKE '%$keyword%'";
}
$count_result = mysqli_query($con, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// 计算偏移量
$offset = ($current_page - 1) * $records_per_page;

/* =========================
   FETCH USERS WITH PAGINATION
========================= */
$sql = "
    SELECT user_id, apu_id, name, username, email, phone_number, role, summary_point, current_point
    FROM user_acc
";

if ($keyword != "") {
    $sql .= "
        WHERE name LIKE '%$keyword%'
        OR username LIKE '%$keyword%'
        OR email LIKE '%$keyword%'
        OR apu_id LIKE '%$keyword%'
    ";
}

$sql .= " ORDER BY user_id DESC LIMIT $offset, $records_per_page";

$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage User Account</title>

<style>
/* ===== BASIC RESET ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
    color: #1b5e20;
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
}

/* ===== LAYOUT ===== */
.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

/* ===== HEADER ===== */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-left h1 {
    font-size: 28px;
    color: #1b5e20;
    margin-bottom: 5px;
}

.header-left p {
    color: #4caf50;
    font-size: 14px;
}

.header-right {
    display: flex;
    gap: 12px;
    align-items: center;
}

.back-btn, .refresh-btn {
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.back-btn {
    background: #607d8b;
    color: white;
}

.back-btn:hover {
    background: #455a64;
    transform: translateY(-2px);
}

.refresh-btn {
    background: #2e7d32;
    color: white;
}

.refresh-btn:hover {
    background: #1b5e20;
    transform: translateY(-2px);
}

/* ===== SEARCH BAR ===== */
.search-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.search-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #2e7d32;
}

.search-box {
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    padding: 12px 18px;
    border-radius: 8px;
    border: 1px solid #c8e6c9;
    font-size: 15px;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #4caf50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
}

.search-btn {
    padding: 12px 24px;
    background: #4caf50;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.search-btn:hover {
    background: #388e3c;
    transform: translateY(-2px);
}

.clear-btn {
    padding: 12px 18px;
    background: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.clear-btn:hover {
    background: #e0e0e0;
}

/* ===== USER COUNTER ===== */
.user-counter {
    background: #e8f5e9;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #2e7d32;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* ===== USER LIST ===== */
.user-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

@media (max-width: 768px) {
    .user-list {
        grid-template-columns: 1fr;
    }
}

.user-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #e8f5e9;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.user-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.user-info h3 {
    font-size: 18px;
    color: #1b5e20;
    margin-bottom: 4px;
}

.user-info .username {
    color: #666;
    font-size: 14px;
}

.role-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-admin {
    background: #ffebee;
    color: #c62828;
}

.role-user {
    background: #e3f2fd;
    color: #1565c0;
}

.role-driver {
    background: #e8f5e9;
    color: #2e7d32;
}

.role-staff {
    background: #fff8e1;
    color: #ff8f00;
}

/* ===== DETAILS ===== */
.user-details {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    font-size: 14px;
}

.detail-row {
    display: flex;
    margin-bottom: 8px;
}

.detail-label {
    font-weight: 600;
    color: #555;
    min-width: 100px;
}

.detail-value {
    color: #333;
}

/* ===== POINTS STYLING ===== */
.points-row {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.point-box {
    background: #f5f5f5;
    padding: 8px 12px;
    border-radius: 6px;
    text-align: center;
    flex: 1;
}

.point-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.point-value {
    font-weight: 700;
    color: #2e7d32;
    font-size: 16px;
}

/* ===== ACTIONS ===== */
.actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.view-btn, .delete-btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.view-btn {
    background: #81c784;
    color: white;
}

.view-btn:hover {
    background: #66bb6a;
    transform: translateY(-2px);
}

.delete-btn {
    background: #ef5350;
    color: white;
}

.delete-btn:hover {
    background: #e53935;
    transform: translateY(-2px);
}

/* ===== PAGINATION ===== */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 30px;
    gap: 8px;
    flex-wrap: wrap;
}

.pagination a, .pagination span {
    padding: 10px 16px;
    background: white;
    border-radius: 6px;
    color: #2e7d32;
    font-weight: 600;
    transition: all 0.3s;
    border: 1px solid #c8e6c9;
    display: inline-block;
    min-width: 40px;
    text-align: center;
}

.pagination a:hover {
    background: #e8f5e9;
    transform: translateY(-2px);
}

.pagination .active {
    background: #4caf50;
    color: white;
    border-color: #4caf50;
}

.pagination .disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ===== NO RESULTS ===== */
.no-results {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 12px;
    grid-column: 1 / -1;
}

.no-results i {
    font-size: 48px;
    color: #c8e6c9;
    margin-bottom: 15px;
}

.no-results h3 {
    color: #666;
    margin-bottom: 10px;
}
</style>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
function toggleDetails(id) {
    var details = document.getElementById("details" + id);
    var btn = document.getElementById("viewBtn" + id);
    
    if (details.style.display === "block") {
        details.style.display = "none";
        btn.innerHTML = '<i class="fas fa-chevron-down"></i> View More';
    } else {
        details.style.display = "block";
        btn.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
    }
}
</script>

</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-users-cog"></i> Manage User Account</h1>
            <p>View and manage all registered users in the system</p>
        </div>
        
        <div class="header-right">
            <a href="AdminPanel.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="ManageUser.php" class="refresh-btn">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>

    <!-- SEARCH SECTION -->
    <div class="search-section">
        <div class="search-title"><i class="fas fa-search"></i> Search Users</div>
        <form class="search-box" method="get">
            <input type="hidden" name="page" value="1">
            <input type="text" name="search" placeholder="Search by name, username, email, or APU ID..."
                   value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if($keyword != ""): ?>
                <a href="ManageUser.php" class="clear-btn">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- USER COUNTER -->
    <div class="user-counter">
        <span><i class="fas fa-users"></i> 
            Total Users: <?php echo $total_records; ?> 
            <?php if($total_pages > 1): ?>
                | Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
            <?php endif; ?>
        </span>
        <?php if($keyword != ""): ?>
            <span>Search results for: "<?php echo htmlspecialchars($keyword); ?>"</span>
        <?php endif; ?>
    </div>

    <!-- USER LIST -->
    <div class="user-list">

        <?php if (mysqli_num_rows($result) == 0): ?>
            <div class="no-results">
                <i class="far fa-folder-open"></i>
                <h3>No users found</h3>
                <p>Try a different search term or add new users</p>
            </div>
        <?php endif; ?>

        <?php while ($u = mysqli_fetch_assoc($result)): 
            // Determine role class for styling
            $role_class = "role-user";
            if ($u['role'] == 'admin') $role_class = "role-admin";
            elseif ($u['role'] == 'driver') $role_class = "role-driver";
            elseif ($u['role'] == 'staff') $role_class = "role-staff";
        ?>

        <div class="user-card">

            <div class="user-header">
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($u['name']); ?></h3>
                    <div class="username">@<?php echo htmlspecialchars($u['username']); ?></div>
                </div>
                <div class="role-badge <?php echo $role_class; ?>">
                    <?php echo htmlspecialchars($u['role']); ?>
                </div>
            </div>

            <div class="actions">
                <button class="view-btn" id="viewBtn<?php echo $u['user_id']; ?>"
                        onclick="toggleDetails(<?php echo $u['user_id']; ?>)">
                    <i class="fas fa-chevron-down"></i> View More
                </button>

                <form method="get" style="display: inline;"
                      onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars(addslashes($u['name'])); ?>? This action cannot be undone.');">
                    <input type="hidden" name="delete_id"
                           value="<?php echo $u['user_id']; ?>">
                    <button type="submit" class="delete-btn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </form>
            </div>

            <div class="user-details" id="details<?php echo $u['user_id']; ?>" style="display: none;">
                <div class="detail-row">
                    <div class="detail-label">APU ID:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($u['apu_id']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($u['email']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($u['phone_number']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">User ID:</div>
                    <div class="detail-value">#<?php echo $u['user_id']; ?></div>
                </div>
                
                <!-- Points Section -->
                <div class="points-row">
                    <div class="point-box">
                        <div class="point-label">Summary Points</div>
                        <div class="point-value"><?php echo $u['summary_point']; ?></div>
                    </div>
                    <div class="point-box">
                        <div class="point-label">Current Points</div>
                        <div class="point-value"><?php echo $u['current_point']; ?></div>
                    </div>
                </div>
            </div>

        </div>

        <?php endwhile; ?>

    </div>

    <!-- PAGINATION -->
    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($current_page > 1): ?>
            <a href="?page=<?php echo $current_page-1; ?>&search=<?php echo urlencode($keyword); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i> Previous</span>
        <?php endif; ?>

        <?php
        // Display page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page+1; ?>&search=<?php echo urlencode($keyword); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
// Auto-submit search when pressing Enter
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if(searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});
</script>

</body>
</html>