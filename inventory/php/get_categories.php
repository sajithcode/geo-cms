<?php
require_once '../../php/config.php';

// Ensure user is logged in
requireLogin();

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only GET requests are accepted.'
    ]);
    exit;
}

try {
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $sortBy = isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'name';
    $sortOrder = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'desc' ? 'DESC' : 'ASC';
    
    // Validate sort column
    $allowedSortColumns = ['name', 'created_at', 'id'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'name';
    }
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build base query
    $baseQuery = "
        FROM inventory_categories c
        LEFT JOIN users u ON c.created_by = u.id
    ";
    
    $whereClause = "";
    $params = [];
    
    // Add search condition
    if (!empty($search)) {
        $whereClause = "WHERE (c.name LIKE ? OR c.description LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) $baseQuery $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    // Get categories with pagination
    $dataQuery = "
        SELECT 
            c.id,
            c.name,
            c.description,
            c.created_at,
            c.created_by,
            u.name as created_by_name,
            u.user_id as created_by_user_id,
            (SELECT COUNT(*) FROM inventory_items WHERE category_id = c.id) as item_count
        $baseQuery
        $whereClause
        ORDER BY c.$sortBy $sortOrder
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $dataStmt = $pdo->prepare($dataQuery);
    $dataStmt->execute($params);
    $categories = $dataStmt->fetchAll();
    
    // Format the data
    $formattedCategories = array_map(function($category) {
        return [
            'id' => intval($category['id']),
            'name' => $category['name'],
            'description' => $category['description'] ?? '',
            'item_count' => intval($category['item_count']),
            'created_at' => formatDate($category['created_at'], 'DD/MM/YYYY HH:mm'),
            'created_at_raw' => $category['created_at'],
            'created_by' => $category['created_by_name'] ?? 'Unknown',
            'created_by_id' => $category['created_by']
        ];
    }, $categories);
    
    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedCategories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => intval($totalCount),
            'limit' => $limit,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev,
            'next_page' => $hasNext ? $page + 1 : null,
            'prev_page' => $hasPrev ? $page - 1 : null
        ],
        'search' => $search,
        'sort' => [
            'by' => $sortBy,
            'order' => $sortOrder
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Get Categories Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'CATEGORIES_FETCH_FAILED'
    ]);
    
} catch (PDOException $e) {
    // Log database errors
    error_log("Database Error in get_categories.php: " . $e->getMessage());
    
    // Return generic error to user
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching categories',
        'error_code' => 'DATABASE_ERROR'
    ]);
}
?>