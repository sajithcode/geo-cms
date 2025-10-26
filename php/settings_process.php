<?php
require_once 'config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_STRING);
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $dashboard_layout = filter_input(INPUT_POST, 'dashboard_layout', FILTER_SANITIZE_STRING);
        $items_per_page = filter_input(INPUT_POST, 'items_per_page', FILTER_VALIDATE_INT);
        $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING);

        // Validate inputs
        $valid_themes = ['light', 'dark', 'auto'];
        $valid_layouts = ['cards', 'list', 'compact'];
        $valid_languages = ['en', 'si'];
        $valid_items_per_page = [10, 25, 50, 100];

        if (!in_array($theme, $valid_themes)) {
            $theme = 'auto';
        }

        if (!in_array($dashboard_layout, $valid_layouts)) {
            $dashboard_layout = 'compact';
        }

        if (!in_array($language, $valid_languages)) {
            $language = 'en';
        }

        if (!in_array($items_per_page, $valid_items_per_page)) {
            $items_per_page = 50;
        }

        // Save settings to session
        $_SESSION['theme'] = $theme;
        $_SESSION['email_notifications'] = $email_notifications;
        $_SESSION['dashboard_layout'] = $dashboard_layout;
        $_SESSION['items_per_page'] = $items_per_page;
        $_SESSION['language'] = $language;

        // Optional: Save to database (you can add a user_settings table later)
        // For now, we'll just use sessions which persist during the user session

        // Log the settings change
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, details)
                VALUES (?, 'settings_updated', ?)
            ");
            $stmt->execute([$user_id, json_encode([
                'theme' => $theme,
                'email_notifications' => $email_notifications,
                'dashboard_layout' => $dashboard_layout,
                'items_per_page' => $items_per_page,
                'language' => $language
            ])]);
        }

        // Redirect back with success message
        $_SESSION['settings_success'] = 'Settings updated successfully!';
        header('Location: ../settings.php');
        exit;

    } else {
        // Invalid request method
        header('Location: ../settings.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Settings process error: " . $e->getMessage());

    // Redirect back with error message
    $_SESSION['settings_error'] = 'Failed to update settings. Please try again.';
    header('Location: ../settings.php');
    exit;
}
?>