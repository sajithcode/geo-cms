<?php
require_once '../../php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $uploadDir = '../../uploads/inventory/';
    $file = $_FILES['test_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed with error code: ' . $file['error'];
    } else {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File size too large. Maximum 5MB allowed';
        } else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'test_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $success = "File uploaded successfully: {$filename}";
                $relativePath = "uploads/inventory/{$filename}";
            } else {
                $error = 'Failed to move uploaded file';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Upload Test Result</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .uploaded-image { max-width: 300px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🖼️ Image Upload Test Result</h1>
    
    <?php if (isset($success)): ?>
        <div class="result success">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php if (isset($relativePath)): ?>
            <h3>Uploaded Image Preview:</h3>
            <img src="../../<?php echo htmlspecialchars($relativePath); ?>" 
                 alt="Uploaded test image" 
                 class="uploaded-image"
                 onerror="this.alt='Image failed to load'">
            <p><strong>Image Path:</strong> <?php echo htmlspecialchars($relativePath); ?></p>
        <?php endif; ?>
    <?php elseif (isset($error)): ?>
        <div class="result error">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div class="result error">
            ❌ No file was uploaded
        </div>
    <?php endif; ?>
    
    <h2>🔗 Navigation</h2>
    <a href="test_image_functionality.php">← Back to Test Page</a> | 
    <a href="../admin-dashboard.php">Admin Dashboard</a>
</body>
</html>