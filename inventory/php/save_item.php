<?php
require_once '../../php/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Handle image upload
function handleImageUpload($item_id = null) {
    if (!isset($_FILES['item_image']) || $_FILES['item_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No image uploaded
    }
    
    $file = $_FILES['item_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Image size too large. Maximum 5MB allowed');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../../uploads/inventory/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = ($item_id ? "item_{$item_id}_" : "item_new_") . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save image');
    }
    
    return 'uploads/inventory/' . $filename;
}

// Delete old image file
function deleteOldImage($imagePath) {
    if ($imagePath && file_exists('../../' . $imagePath)) {
        unlink('../../' . $imagePath);
    }
}

try {
    $item_id = $_POST['item_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $quantity_total = (int)($_POST['quantity_total'] ?? 0);
    $quantity_available = (int)($_POST['quantity_available'] ?? 0);
    $quantity_borrowed = (int)($_POST['quantity_borrowed'] ?? 0);
    $quantity_maintenance = (int)($_POST['quantity_maintenance'] ?? 0);
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit;
    }
    
    if ($quantity_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total quantity must be greater than 0']);
        exit;
    }
    
    // Validate quantity consistency
    if (($quantity_available + $quantity_borrowed + $quantity_maintenance) !== $quantity_total) {
        echo json_encode(['success' => false, 'message' => 'Quantity breakdown must equal total quantity']);
        exit;
    }
    
    // If category_id is provided, validate it exists
    if (!empty($category_id)) {
        $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid category selected']);
            exit;
        }
    } else {
        $category_id = null;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        $image_path = null;
        $old_image_path = null;
        
        if (!empty($item_id)) {
            // Get old image path for cleanup
            $stmt = $pdo->prepare("SELECT image_path FROM inventory_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $old_item = $stmt->fetch();
            $old_image_path = $old_item ? $old_item['image_path'] : null;
        }
        
        // Handle image upload
        try {
            $uploaded_image = handleImageUpload($item_id);
            if ($uploaded_image) {
                $image_path = $uploaded_image;
                // Delete old image if we have a new one
                if ($old_image_path && $old_image_path !== $image_path) {
                    deleteOldImage($old_image_path);
                }
            } else {
                // Check if current image should be removed
                if (isset($_POST['remove_current_image']) && $_POST['remove_current_image'] === 'true') {
                    if ($old_image_path) {
                        deleteOldImage($old_image_path);
                    }
                    $image_path = null;
                } else {
                    // Keep existing image if no new image uploaded
                    $image_path = $old_image_path;
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        
        if (!empty($item_id)) {
            // Update existing item
            $stmt = $pdo->prepare("
                UPDATE inventory_items 
                SET name = ?, description = ?, image_path = ?, category_id = ?, 
                    quantity_total = ?, quantity_available = ?, 
                    quantity_borrowed = ?, quantity_maintenance = ?,
                    updated_at = NOW(), updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $image_path, $category_id, 
                $quantity_total, $quantity_available, 
                $quantity_borrowed, $quantity_maintenance,
                $user_id, $item_id
            ]);
            
            $action = 'item_updated';
            $message = 'Item updated successfully';
            $result_id = $item_id;
            
        } else {
            // Create new item
            $stmt = $pdo->prepare("
                INSERT INTO inventory_items (
                    name, description, image_path, category_id, 
                    quantity_total, quantity_available, 
                    quantity_borrowed, quantity_maintenance,
                    status, created_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), ?)
            ");
            $stmt->execute([
                $name, $description, $image_path, $category_id, 
                $quantity_total, $quantity_available, 
                $quantity_borrowed, $quantity_maintenance,
                $user_id
            ]);
            
            $result_id = $pdo->lastInsertId();
            
            // Update image filename with actual item ID
            if ($image_path && strpos($image_path, 'item_new_') !== false) {
                $old_path = '../../' . $image_path;
                $new_filename = str_replace('item_new_', "item_{$result_id}_", basename($image_path));
                $new_path = '../../uploads/inventory/' . $new_filename;
                $new_image_path = 'uploads/inventory/' . $new_filename;
                
                if (rename($old_path, $new_path)) {
                    $image_path = $new_image_path;
                    // Update database with correct path
                    $stmt = $pdo->prepare("UPDATE inventory_items SET image_path = ? WHERE id = ?");
                    $stmt->execute([$image_path, $result_id]);
                }
            }
            
            $action = 'item_created';
            $message = 'Item created successfully';
        }
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (
                user_id, action, details, created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $details = json_encode([
            'item_id' => $result_id,
            'item_name' => $name,
            'quantity_total' => $quantity_total
        ]);
        $stmt->execute([$user_id, $action, $details]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'item_id' => $result_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in save_item.php: " . $e->getMessage());
    
    // Check for duplicate name error
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'An item with this name already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred while saving the item']);
    }
}
?>