<?php
require_once '../../php/config.php';

// Ensure user is logged in and has appropriate permissions
requireLogin();

// Check if user has admin privileges (only admin can create categories)
if (!hasRole('admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can create categories.'
    ]);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON input is not available, try to get from POST data
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate CSRF token if provided
    if (isset($input['csrf_token']) && !verifyCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Validate required fields
    if (empty($input['name'])) {
        throw new Exception('Category name is required');
    }
    
    // Sanitize input
    $name = sanitizeInput($input['name']);
    $description = isset($input['description']) ? sanitizeInput($input['description']) : '';
    
    // Additional validation
    if (strlen($name) < 2) {
        throw new Exception('Category name must be at least 2 characters long');
    }
    
    if (strlen($name) > 100) {
        throw new Exception('Category name must not exceed 100 characters');
    }
    
    if (strlen($description) > 1000) {
        throw new Exception('Description must not exceed 1000 characters');
    }
    
    // Check if category name already exists
    $checkStmt = $pdo->prepare("SELECT id FROM store_categories WHERE name = ?");
    $checkStmt->execute([$name]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('A category with this name already exists');
    }
    
    // Insert new category
    $insertStmt = $pdo->prepare(""
        INSERT INTO store_categories (name, description, created_by) 
        VALUES (?, ?, ?)
    ""
    );
    $createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $success = $insertStmt->execute([$name, $description, $createdBy]);
    
    if (!$success) {
        throw new Exception('Failed to create category');
    }
    
    // Get the ID of the newly created category
    $categoryId = $pdo->lastInsertId();
    
    // Log the activity
    if (isset($_SESSION['user_id'])) {
        logActivity(
            $_SESSION['user_id'],
            'create_category',
            "Created category: $name (ID: $categoryId)",
            $_SERVER['REMOTE_ADDR'] ?? null
        );
        
        // Create notification for successful creation
        createNotification(
            $_SESSION['user_id'],
            'Category Created',
            "Successfully created category: $name",
            'success'
        );
    }
    
    // Fetch the created category with full details
    $categoryStmt = $pdo->prepare(""
        SELECT 
            c.*,
            u.name as created_by_name,
            u.user_id as created_by_user_id
        FROM store_categories c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
    ""
    );
    $category = $categoryStmt->fetch();
    
    // Return success response with category data
    echo json_encode([
        'success' => true,
        'message' => 'Category created successfully',
        'category' => [
            'id' => $category['id'],
            'name' => $category['name'],
            'description' => $category['description'],
            'created_at' => formatDate($category['created_at'], 'DD/MM/YYYY HH:mm'),
            'created_by' => $category['created_by_name'] ?? 'Unknown'
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Create Category Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'CATEGORY_CREATION_FAILED'
    ]);
    
} catch (PDOException $e) {
    // Log database errors
    error_log("Database Error in create_category.php: " . $e->getMessage());
    
    // Return generic error to user
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while creating category',
        'error_code' => 'DATABASE_ERROR'
    ]);
}
?>
