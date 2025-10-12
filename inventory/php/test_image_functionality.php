<?php
require_once '../../php/config.php';

// Test image functionality
$test_results = [];

// Test 1: Check if image column exists
try {
    $stmt = $pdo->prepare("DESCRIBE inventory_items");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('image_path', $columns)) {
        $test_results[] = '✅ image_path column exists';
    } else {
        $test_results[] = '❌ image_path column missing';
    }
} catch (Exception $e) {
    $test_results[] = '❌ Database error: ' . $e->getMessage();
}

// Test 2: Check if uploads directory exists and is writable
$uploadDir = '../../uploads/inventory/';
if (is_dir($uploadDir)) {
    $test_results[] = '✅ Upload directory exists';
    if (is_writable($uploadDir)) {
        $test_results[] = '✅ Upload directory is writable';
    } else {
        $test_results[] = '❌ Upload directory is not writable';
    }
} else {
    $test_results[] = '❌ Upload directory does not exist';
}

// Test 3: Check if security .htaccess exists
if (file_exists($uploadDir . '.htaccess')) {
    $test_results[] = '✅ Security .htaccess file exists';
} else {
    $test_results[] = '❌ Security .htaccess file missing';
}

// Test 4: Try to select items with images
try {
    $stmt = $pdo->prepare("SELECT id, name, image_path FROM inventory_items WHERE image_path IS NOT NULL LIMIT 5");
    $stmt->execute();
    $itemsWithImages = $stmt->fetchAll();
    
    $test_results[] = '✅ Items with images query works';
    $test_results[] = 'ℹ️ Found ' . count($itemsWithImages) . ' items with images';
    
    foreach ($itemsWithImages as $item) {
        $imagePath = '../../' . $item['image_path'];
        if (file_exists($imagePath)) {
            $test_results[] = "✅ Image exists for: {$item['name']}";
        } else {
            $test_results[] = "⚠️ Image missing for: {$item['name']} (Path: {$item['image_path']})";
        }
    }
} catch (Exception $e) {
    $test_results[] = '❌ Error querying items with images: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Functionality Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 5px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🖼️ Image Functionality Test Results</h1>
    
    <?php foreach ($test_results as $result): ?>
        <?php
        $class = 'info';
        if (strpos($result, '✅') !== false) {
            $class = 'success';
        } elseif (strpos($result, '❌') !== false) {
            $class = 'error';
        } elseif (strpos($result, '⚠️') !== false) {
            $class = 'warning';
        }
        ?>
        <div class="result <?php echo $class; ?>">
            <?php echo htmlspecialchars($result); ?>
        </div>
    <?php endforeach; ?>
    
    <h2>📝 Test Upload Form</h2>
    <form action="test_image_upload.php" method="post" enctype="multipart/form-data">
        <label for="test_image">Test Image Upload:</label><br>
        <input type="file" name="test_image" id="test_image" accept="image/*"><br><br>
        <button type="submit">Test Upload</button>
    </form>
    
    <h2>🔗 Navigation</h2>
    <a href="../admin-dashboard.php">← Back to Admin Dashboard</a> | 
    <a href="../student-dashboard.php">Student Dashboard</a>
</body>
</html>