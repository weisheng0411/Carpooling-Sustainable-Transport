<?php
// 数据库连接
$conn = mysqli_connect("localhost", "root", "", "wdd");
if (!$conn) {
    die("Connection failed");
}

// 处理删除
if (isset($_GET['delete'])) {
    $item_id = $_GET['delete'];
    $sql = "DELETE FROM items WHERE Items_ID = $item_id";
    mysqli_query($conn, $sql);
    echo "<script>alert('Reward deleted!');</script>";
}

// 处理更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_reward'])) {
    $item_id = $_POST['item_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $points = $_POST['points'];
    $stock = $_POST['stock'];
    
    $sql = "UPDATE items SET 
            Name = '$name', 
            Points_Required = '$points', 
            Stock = '$stock' 
            WHERE Items_ID = $item_id";
    mysqli_query($conn, $sql);
    echo "<script>alert('Reward updated!');</script>";
}

// 搜索
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// 获取数据
$sql = "SELECT * FROM items";
if (!empty($search)) {
    $sql .= " WHERE Name LIKE '%$search%'";
}
$sql .= " ORDER BY Items_ID DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Rewards</title>

<style>
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

    main {
        max-width: 1200px;
        margin: 30px auto;
        padding: 30px 40px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    /* HEADER SECTION */
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .header-left {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex: 1;
    }

    .header-right {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .backBtn, .refreshBtn {
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
    }

    .backBtn {
        background: #607d8b;
        color: white;
    }

    .backBtn:hover {
        background: #455a64;
        transform: translateY(-2px);
    }

    .refreshBtn {
        background: #2e7d32;
        color: white;
    }

    .refreshBtn:hover {
        background: #1b5e20;
        transform: translateY(-2px);
    }

    h2 {
        text-align: center;
        font-size: 28px;
        color: #1b5e20;
        margin: 0;
        flex: 1;
    }

    hr {
        background-color: #c8e6c9;
        border: none;
        height: 2px;
        margin: 10px 0 25px 0;
    }

    /* SEARCH BAR SECTION */
    .top-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
        background: #f8f9f8;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .search-input {
        flex: 1;
        padding: 12px 18px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #4caf50;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
    }

    button {
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    button:hover {
        transform: translateY(-2px);
    }

    .btn-search {
        background: #4caf50;
        color: white;
    }

    .btn-search:hover {
        background: #388e3c;
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    }

    .btn-add {
        background: #2196f3;
        color: white;
    }

    .btn-add:hover {
        background: #1976d2;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
    }

    /* REWARD LIST SECTION */
    .reward-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    @media (max-width: 900px) {
        .reward-list {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .reward-list {
            grid-template-columns: 1fr;
        }
    }

    .reward {
        background: white;
        padding: 25px;
        border-radius: 12px;
        border: 1px solid #e8f5e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .reward:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-color: #c8e6c9;
    }

    label {
        display: block;
        font-weight: 600;
        margin-top: 15px;
        margin-bottom: 6px;
        color: #2e7d32;
        font-size: 14px;
    }

    input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
        background: #f8f9f8;
    }

    input:focus {
        outline: none;
        border-color: #4caf50;
        background: white;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* ACTION BUTTONS IN REWARD CARDS */
    .btn {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        gap: 12px;
    }

    .btn button {
        flex: 1;
        padding: 10px 16px;
        font-size: 14px;
    }

    .save {
        background: #4caf50;
        color: white;
    }

    .save:hover {
        background: #388e3c;
        box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
    }

    .delete {
        background: #f44336;
        color: white;
    }

    .delete:hover {
        background: #d32f2f;
        box-shadow: 0 4px 10px rgba(244, 67, 54, 0.2);
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
        main {
            padding: 20px;
            margin: 15px;
        }
        
        .header {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
        
        .header-left {
            order: 1;
            width: 100%;
        }
        
        .header-right {
            order: 2;
            justify-content: center;
            width: 100%;
        }
        
        h2 {
            margin-bottom: 10px;
        }
        
        .top-bar {
            flex-direction: column;
        }
        
        .top-bar input,
        .top-bar button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        main {
            padding: 15px;
            margin: 10px;
        }
        
        .btn {
            flex-direction: column;
        }
        
        .btn button {
            width: 100%;
        }
        
        .backBtn, .refreshBtn {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .header-right {
            gap: 8px;
        }
    }

    /* EMPTY STATE */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px 20px;
        background: #f8f9f8;
        border-radius: 12px;
        border: 2px dashed #c8e6c9;
        color: #666;
    }

    .empty-state h3 {
        color: #2e7d32;
        margin-bottom: 10px;
    }
</style>
</head>

<body>

<main>

    <div class='header'>
    <div class="header-left">
        <h2>Edit Rewards</h2>
    </div>
    <div class="header-right">
        <button type='button' class='backBtn' onclick="window.location.href='AdminPanel.php'">← Back</button>
        <button type='button' class='refreshBtn' onclick="window.location.href='EditRewards.php'">↻ Refresh</button>
    </div>
</div>

    <form class="top-bar" method="GET">
        <input type="text" class="search-input" name="search" placeholder="Search rewards..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn-search" type="submit">Search</button>
        <button class="btn-add" type="button" onclick="window.location.href='AddRewards.php'">Add New Reward</button>
    </form>

    <div class="reward-list">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $item_id = $row['Items_ID'];
                $name = htmlspecialchars($row['Name']);
                $points = $row['Points_Required'];
                $stock = $row['Stock'];
        ?>
        <form class="reward" method="POST">
            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
            
            <label>Name</label>
            <input type="text" name="name" value="<?php echo $name; ?>" required>

            <label>Points Required</label>
            <input type="number" name="points" value="<?php echo $points; ?>" required min="0">

            <label>Stock</label>
            <input type="number" name="stock" value="<?php echo $stock; ?>" required min="0">

            <div class="btn">
                <button class="save" type="submit" name="save_reward">Save</button>
                <button class="delete" type="button" 
                        onclick="if(confirm('Delete this reward?')) window.location.href='?delete=<?php echo $item_id; ?>'">
                    Delete
                </button>
            </div>
        </form>
        <?php
            }
        } else {
            echo '<div style="width:100%; text-align:center; padding:40px; color:#666;">No rewards found.</div>';
        }
        ?>
    </div>

</main>

</body>
</html>

<?php
mysqli_close($conn);
?>