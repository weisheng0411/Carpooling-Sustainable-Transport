<?php
// connect db
$conn = mysqli_connect("localhost", "root", "", "wdd");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['issue'];
    $description = $_POST['description'];
    $user_id = 1;
    
    $photo_url = "";
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        // 获取文件临时路径
        $tmp_name = $_FILES['attachment']['tmp_name'];
        
        // 将图片转为 Base64 编码
        $image_data = file_get_contents($tmp_name);
        $photo_url = "data:image/jpeg;base64," . base64_encode($image_data);
    }
    
    // 插入数据库
    $sql = "INSERT INTO customer_service (title, description, photo_url, user_id) 
            VALUES ('$title', '$description', '$photo_url', '$user_id')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>window.location.href='SubmitSuccess.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Service - Submit Issue</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-color: #2e7d32;
        --primary-light: #4caf50;
        --primary-dark: #1b5e20;
        --secondary-color: #2196f3;
        --secondary-light: #64b5f6;
        --danger-color: #dc3545;
        --danger-light: #e57373;
        --success-color: #28a745;
        --success-light: #66bb6a;
        --warning-color: #ff9800;
        --text-dark: #333;
        --text-medium: #555;
        --text-light: #777;
        --bg-light: #f8f9fa;
        --bg-white: #ffffff;
        --border-color: #dee2e6;
        --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.12);
        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 16px;
        --transition: all 0.3s ease;
    }

    * { 
        box-sizing: border-box; 
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        min-height: 100vh;
        color: var(--text-dark);
        line-height: 1.6;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .container {
        width: 90%;
        max-width: 700px;
        background: var(--bg-white);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-medium);
        overflow: hidden;
        border-left: 4px solid var(--primary-color);
        margin: 0 auto;
    }

    .header-section {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        padding: 25px 30px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .header-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
        transform: translateX(-100%);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .page-subtitle {
        font-size: 16px;
        opacity: 0.9;
        font-weight: 400;
        position: relative;
        z-index: 1;
    }

    .title-icon {
        background: rgba(255, 255, 255, 0.15);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 24px;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .content-section {
        padding: 35px;
    }

    /* 表单样式 */
    .form-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .form-group {
        margin-bottom: 5px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--text-dark);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: var(--primary-color);
        width: 20px;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius-md);
        font-size: 15px;
        transition: var(--transition);
        background: var(--bg-light);
        color: var(--text-dark);
        font-family: inherit;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        background: var(--bg-white);
    }

    .form-input::placeholder, .form-textarea::placeholder {
        color: var(--text-light);
        opacity: 0.7;
    }

    .form-textarea {
        min-height: 140px;
        resize: vertical;
        line-height: 1.5;
    }

    /* 文件上传样式 */
    .file-upload-container {
        position: relative;
        margin-top: 5px;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        background: var(--bg-light);
        border: 2px dashed var(--border-color);
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-medium);
        font-weight: 500;
    }

    .file-upload-label:hover {
        background: #f0f9f0;
        border-color: var(--primary-light);
        color: var(--primary-color);
    }

    .file-upload-label i {
        font-size: 20px;
        color: var(--primary-color);
    }

    .file-name {
        margin-top: 10px;
        font-size: 14px;
        color: var(--text-medium);
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .file-name i {
        color: var(--primary-color);
    }

    #fileInput {
        display: none;
    }

    /* 按钮样式 */
    .button-group {
        display: flex;
        gap: 20px;
        margin-top: 30px;
    }

    .form-button {
        flex: 1;
        padding: 16px;
        border-radius: var(--border-radius-md);
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: var(--shadow-light);
    }

    .submit-button {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
    }

    .submit-button:hover {
        background: linear-gradient(135deg, var(--primary-dark), #1b5e20);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .cancel-button {
        background: linear-gradient(135deg, #616161, #424242);
        color: white;
    }

    .cancel-button:hover {
        background: linear-gradient(135deg, #424242, #212121);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .form-button:active {
        transform: translateY(0);
    }

    /* 帮助文本 */
    .help-text {
        font-size: 14px;
        color: var(--text-light);
        margin-top: 8px;
        padding-left: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .help-text i {
        color: var(--primary-light);
    }

    /* 响应式设计 */
    @media (max-width: 768px) {
        .container {
            width: 95%;
        }
        
        .content-section {
            padding: 25px;
        }
        
        .button-group {
            flex-direction: column;
            gap: 15px;
        }
        
        .header-section {
            padding: 20px;
        }
        
        .page-title {
            font-size: 24px;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 10px;
        }
        
        .content-section {
            padding: 20px;
        }
        
        .form-input, .form-textarea {
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .file-upload-label {
            padding: 14px;
            font-size: 14px;
        }
        
        .page-title {
            font-size: 22px;
        }
    }

    /* 状态指示器 */
    .required::after {
        content: " *";
        color: var(--danger-color);
    }

    /* 表单验证样式 */
    .form-input:invalid:not(:placeholder-shown),
    .form-textarea:invalid:not(:placeholder-shown) {
        border-color: var(--danger-light);
    }

    .form-input:valid:not(:placeholder-shown),
    .form-textarea:valid:not(:placeholder-shown) {
        border-color: var(--success-light);
    }
</style>
</head>
<body>

<div class='container'>
    <!-- 头部区域 -->
    <div class='header-section'>
        <div class="title-icon">
            <i class="fas fa-headset"></i>
        </div>
        <h1 class='page-title'>Customer Service</h1>
        <p class='page-subtitle'>Submit an issue and we'll help you resolve it</p>
    </div>

    <!-- 内容区域 -->
    <div class='content-section'>
        <form action='' method='POST' enctype="multipart/form-data" class='form-container'>
            <!-- 问题标题 -->
            <div class='form-group'>
                <label class='form-label' for="issue">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="required">Issue Title</span>
                </label>
                <input type="text" 
                       id="issue" 
                       name="issue" 
                       class='form-input' 
                       placeholder="Briefly describe your issue" 
                       required
                       maxlength="100">
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Be specific to help us understand your issue better
                </p>
            </div>

            <!-- 问题描述 -->
            <div class='form-group'>
                <label class='form-label' for="description">
                    <i class="fas fa-align-left"></i>
                    <span class="required">Description</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          class='form-textarea' 
                          placeholder="Provide detailed information about your issue. Include steps to reproduce if applicable."
                          required
                          maxlength="500"></textarea>
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Maximum 500 characters. Include any error messages or relevant details.
                </p>
            </div>

            <!-- 文件上传 -->
            <div class='form-group'>
                <label class='form-label'>
                    <i class="fas fa-paperclip"></i>
                    Attachment (Optional)
                </label>
                <div class="file-upload-container">
                    <input type="file" 
                           id="fileInput" 
                           name="attachment" 
                           accept=".jpg,.jpeg,.png,.pdf">
                    <label for="fileInput" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload Photo or Document (JPG, PNG, PDF)</span>
                    </label>
                    <div id="fileName" class="file-name" style="display: none;">
                        <i class="fas fa-file-alt"></i>
                        <span></span>
                    </div>
                </div>
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Maximum file size: 5MB. Supported formats: JPG, PNG, PDF
                </p>
            </div>

            <!-- 按钮组 -->
            <div class='button-group'>
                <button type='submit' class='form-button submit-button'>
                    <i class="fas fa-paper-plane"></i>
                    Submit Issue
                </button>
                <button type='button' 
                        onclick="window.location.href='../Aston/profile.php'" 
                        class='form-button cancel-button'>
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileName');
        const fileNameSpan = fileNameDisplay.querySelector('span');
        
        // 文件上传处理
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileNameSpan.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                fileNameDisplay.style.display = 'flex';
            } else {
                fileNameDisplay.style.display = 'none';
            }
        });
        
        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 表单提交验证
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const issueInput = document.getElementById('issue');
            const descriptionInput = document.getElementById('description');
            
            if (!issueInput.value.trim()) {
                e.preventDefault();
                alert('Please enter an issue title.');
                issueInput.focus();
                return false;
            }
            
            if (!descriptionInput.value.trim()) {
                e.preventDefault();
                alert('Please provide a description of your issue.');
                descriptionInput.focus();
                return false;
            }
            
            // 文件大小验证 (5MB限制)
            if (fileInput.files.length > 0) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (fileInput.files[0].size > maxSize) {
                    e.preventDefault();
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    return false;
                }
            }
            
            // 显示加载状态
            const submitBtn = form.querySelector('.submit-button');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
        });
    });
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>