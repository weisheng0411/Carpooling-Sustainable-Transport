<?php
// 数据库连接
$conn = mysqli_connect("localhost", "root", "", "wdd");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ========== 处理删除请求 ==========
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $news_id = intval($_GET['id']);
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // 获取图片路径以便删除文件
    $sql = "SELECT image FROM news WHERE news_id = $news_id";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        // 如果存在图片文件，删除它
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
    }
    
    // 从数据库中删除记录
    $delete_sql = "DELETE FROM news WHERE news_id = $news_id";
    
    if (mysqli_query($conn, $delete_sql)) {
        $message = "News ID $news_id deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting news: " . mysqli_error($conn);
        $message_type = "error";
    }
    
    // 保存搜索参数
    $search_param = !empty($search) ? "&search=" . urlencode($search) : "";
    
    // 重定向回当前页面
    header("Location: EditNews.php?message=" . urlencode($message) . "&type=" . $message_type . $search_param);
    exit();
}

// ========== 处理表单更新 ==========
$message = "";
$message_type = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['news_id'])) {
    $news_id = intval($_POST['news_id']);
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // 处理图片上传
    $image = $_POST['existing_image']; // 默认使用现有图片
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // 验证文件类型
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            // 生成唯一文件名
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_news_' . $news_id . '.' . $file_extension;
            $targetFile = $fileName;
            
            // 移动上传的文件
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $targetFile;
                // 删除旧图片（可选）
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            }
        }
    }
    
    // 更新数据库
    $update_sql = "UPDATE news SET 
                   title = '$title',
                   content = '$content',
                   image = '$image',
                   date = '$date'
                   WHERE news_id = $news_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "News ID $news_id updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating news: " . mysqli_error($conn);
        $message_type = "error";
    }
    
    // 保存搜索参数
    $search_param = !empty($search) ? "&search=" . urlencode($search) : "";
    
    // 重定向回当前页面，避免表单重复提交
    header("Location: EditNews.php?message=" . urlencode($message) . "&type=" . $message_type . $search_param);
    exit();
}

// ========== 处理消息参数 ==========
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'] ?? 'info';
}

// ========== 处理搜索 ==========
$search = "";
$where_clause = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $where_clause = "WHERE title LIKE '%$search%' OR content LIKE '%$search%'";
}

// ========== 获取所有新闻 ==========
$sql = "SELECT * FROM news $where_clause ORDER BY date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit News | Admin Panel</title>

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
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
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

    .btn-create {
        background: #2196f3;
        color: white;
    }

    .btn-create:hover {
        background: #1976d2;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
    }

    /* NEWS LIST SECTION */
    .news-list {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .news-card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid #e8f5e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .news-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-color: #c8e6c9;
    }

    .news-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e8f5e9;
    }

    .news-id {
        background: #f8f9f8;
        color: #666;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #c8e6c9;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #2e7d32;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-group label::before {
        content: "•";
        color: #4caf50;
        font-size: 16px;
    }

    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="file"] {
        width: 100%;
        padding: 12px 14px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
        background: #f8f9f8;
    }

    .form-group input:focus {
        outline: none;
        border-color: #4caf50;
        background: white;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    .form-group textarea {
        width: 100%;
        padding: 12px 14px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
        background: #f8f9f8;
        resize: vertical;
        min-height: 120px;
        line-height: 1.5;
        font-family: inherit;
    }

    .form-group textarea:focus {
        outline: none;
        border-color: #4caf50;
        background: white;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    /* IMAGE PREVIEW */
    .image-preview-container {
        margin-top: 10px;
    }

    .image-preview {
        max-width: 250px;
        max-height: 180px;
        border-radius: 8px;
        border: 2px solid #c8e6c9;
        padding: 5px;
        background: white;
        margin-bottom: 10px;
        display: block;
        transition: all 0.3s;
    }

    .image-preview:hover {
        border-color: #4caf50;
        transform: scale(1.02);
    }

    .no-image {
        color: #666;
        font-style: italic;
        background: #f8f9f8;
        padding: 10px;
        border-radius: 6px;
        border: 1px dashed #c8e6c9;
        text-align: center;
    }

    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e8f5e9;
        gap: 15px;
    }

    .btn-save, .btn-reload, .btn-delete {
        flex: 1;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-save {
        background: #4caf50;
        color: white;
    }

    .btn-save:hover {
        background: #388e3c;
        box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
    }

    .btn-reload {
        background: #607d8b;
        color: white;
    }

    .btn-reload:hover {
        background: #455a64;
        box-shadow: 0 4px 10px rgba(96, 125, 139, 0.2);
    }

    .btn-delete {
        background: #f44336;
        color: white;
    }

    .btn-delete:hover {
        background: #d32f2f;
        box-shadow: 0 4px 10px rgba(244, 67, 54, 0.2);
    }

    /* EMPTY STATE */
    .empty-state {
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
        font-size: 20px;
    }

    .empty-state p {
        margin-bottom: 20px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    /* DATE INFO */
    .date-info {
        font-size: 13px;
        color: #666;
        background: #f8f9f8;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #c8e6c9;
        display: inline-block;
        margin-top: 5px;
    }

    /* MESSAGE BOX */
    .message-box {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
        animation-fill-mode: forwards;
        max-width: 300px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    
    .message-success {
        background: #4CAF50;
        color: white;
        border-left: 4px solid #2E7D32;
    }
    
    .message-error {
        background: #f44336;
        color: white;
        border-left: 4px solid #c62828;
    }
    
    .message-info {
        background: #2196F3;
        color: white;
        border-left: 4px solid #0D47A1;
    }
    
    .message-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
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
        
        .news-card {
            padding: 20px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn-save, .btn-reload, .btn-delete {
            width: 100%;
        }
        
        .message-box {
            left: 20px;
            right: 20px;
            max-width: none;
        }
    }

    @media (max-width: 480px) {
        main {
            padding: 15px;
            margin: 10px;
        }
        
        .backBtn, .refreshBtn {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .header-right {
            gap: 8px;
        }
        
        .form-group label {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .image-preview {
            max-width: 100%;
        }
    }
</style>
</head>

<body>

<main>

    <!-- 显示消息 -->
    <?php if (!empty($message)): ?>
    <div class="message-box message-<?php echo $message_type; ?>" id="messageBox">
        <span><?php echo htmlspecialchars($message); ?></span>
        <button class="message-close" onclick="document.getElementById('messageBox').remove()">×</button>
    </div>
    <script>
        // 5秒后自动移除消息
        setTimeout(function() {
            var msg = document.getElementById('messageBox');
            if (msg) msg.remove();
        }, 5000);
    </script>
    <?php endif; ?>

    <div class='header'>
        <div class="header-left">
            <h2>Edit News</h2>
        </div>
        <div class="header-right">
            <button type='button' class='backBtn' onclick="window.location.href='AdminPanel.php'">← Back</button>
            <button type='button' class='refreshBtn' onclick="window.location.href='EditNews.php'">↻ Refresh</button>
        </div>
    </div>

    <form class="top-bar" method="GET">
        <input type="text" class="search-input" name="search" placeholder="Search news by title or content..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn-search" type="submit">Search</button>
        <button class="btn-create" type="button" onclick="window.location.href='http://localhost/ASS-WDD/LK/AddNews.php'">Create News</button>
    </form>

    <div class="news-list">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $news_id = $row['news_id'];
                $title = htmlspecialchars($row['title']);
                $content = htmlspecialchars($row['content']);
                $image = $row['image'];
                $date = $row['date'];
                $formatted_date = date('F d, Y', strtotime($date));
        ?>
        <!-- 表单提交到当前页面 -->
        <form class="news-card" method="POST" action="" enctype="multipart/form-data">
            <!-- 隐藏字段传递搜索参数 -->
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            
            <div class="news-header">
                <h3 style="color: #1b5e20; font-size: 18px; margin: 0;">News Entry</h3>
                <span class="news-id">ID: <?php echo str_pad($news_id, 3, '0', STR_PAD_LEFT); ?></span>
            </div>
            
            <input type="hidden" name="news_id" value="<?php echo $news_id; ?>">
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo $title; ?>" required>
            </div>

            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="5" required><?php echo $content; ?></textarea>
            </div>

            <div class="form-group">
                <label>Image</label>
                <div class="image-preview-container">
                    <?php if (!empty($image)): ?>
                        <img src="/ASS-WDD/uploads/<?php echo htmlspecialchars($image); ?>" alt="News Image" class="image-preview">
                        <p class="date-info">Current Image</p>
                    <?php else: ?>
                        <div class="no-image">No image uploaded</div>
                    <?php endif; ?>
                </div>
                <input type="file" name="image" accept="image/*">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($image); ?>">
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" required>
                <p class="date-info">Currently set to: <?php echo $formatted_date; ?></p>
            </div>

            <div class="form-actions">
                <button class="btn-save" type="submit">Save Changes</button>
                <button class="btn-reload" type="reset">Reload Original</button>
                <button class="btn-delete" type="button" 
                        onclick="if(confirm('Are you sure you want to delete this news entry?\nThis action cannot be undone.')) {
                            // 在当前页面删除，保持搜索参数
                            window.location.href='EditNews.php?action=delete&id=<?php echo $news_id; ?>&search=<?php echo urlencode($search); ?>';
                        }">
                    Delete
                </button>
            </div>
        </form>
        <?php
            }
        } else {
            echo '<div class="empty-state">
                    <h3>No News Found</h3>
                    <p>'.(empty($search) ? 'No news entries available. Create your first news article!' : 'No news matches your search.').'</p>
                    <button class="btn-create" type="button" onclick="window.location.href=\'http://localhost/ASS-WDD/LK/AddNews.php\'">Create First News</button>
                  </div>';
        }
        ?>
    </div>

</main>

<script>
    // 文件预览功能
    document.addEventListener('DOMContentLoaded', function() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = this.files[0];
                const parent = this.closest('.form-group');
                const previewContainer = parent.querySelector('.image-preview-container');
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        let img = previewContainer.querySelector('img');
                        if (!img) {
                            img = document.createElement('img');
                            img.className = 'image-preview';
                            previewContainer.innerHTML = '';
                            previewContainer.appendChild(img);
                        }
                        img.src = e.target.result;
                        
                        // 移除无图像消息
                        const noImageMsg = previewContainer.querySelector('.no-image');
                        if (noImageMsg) {
                            noImageMsg.remove();
                        }
                        
                        // 添加当前图片提示
                        const dateInfo = previewContainer.querySelector('.date-info');
                        if (!dateInfo) {
                            const info = document.createElement('p');
                            info.className = 'date-info';
                            info.textContent = 'New image preview';
                            previewContainer.appendChild(info);
                        }
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // 表单提交确认
        const forms = document.querySelectorAll('form.news-card');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const title = this.querySelector('input[name="title"]').value.trim();
                const content = this.querySelector('textarea[name="content"]').value.trim();
                
                if (!title) {
                    e.preventDefault();
                    alert('Please enter a title for the news article.');
                    this.querySelector('input[name="title"]').focus();
                    return false;
                }
                
                if (!content) {
                    e.preventDefault();
                    alert('Please enter content for the news article.');
                    this.querySelector('textarea[name="content"]').focus();
                    return false;
                }
                
                // 显示加载状态
                const saveBtn = this.querySelector('.btn-save');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<span>Saving...</span>';
                saveBtn.disabled = true;
                
                // 防止重复提交
                this.querySelectorAll('button').forEach(btn => {
                    btn.disabled = true;
                });
            });
        });
        
        // 重置表单确认
        const reloadButtons = document.querySelectorAll('.btn-reload');
        reloadButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('form');
                if (form) {
                    const confirmReset = confirm('Are you sure you want to reload the original values? All unsaved changes will be lost.');
                    if (!confirmReset) {
                        e.preventDefault();
                    }
                }
            });
        });
        
        // 删除确认的增强版本
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const newsId = this.closest('form').querySelector('input[name="news_id"]').value;
                const newsTitle = this.closest('form').querySelector('input[name="title"]').value;
                
                if (confirm(`Are you sure you want to delete this news entry?\n\nTitle: "${newsTitle}"\nID: ${newsId}\n\nThis action cannot be undone!`)) {
                    // 显示删除中状态
                    this.innerHTML = '<span>Deleting...</span>';
                    this.disabled = true;
                } else {
                    e.preventDefault();
                }
            });
        });
        
        // 页面加载时滚动到顶部（如果有消息）
        if (document.getElementById('messageBox')) {
            window.scrollTo(0, 0);
        }
    });
</script>

</body>
</html>

<?php
// 关闭数据库连接
if (isset($conn)) {
    mysqli_close($conn);
}
?>