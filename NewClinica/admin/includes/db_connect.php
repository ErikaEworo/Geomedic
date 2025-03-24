<?php
// Path to config file might vary depending on the file structure
if (file_exists(__DIR__ . '/../../admin/config.php')) {
    require_once __DIR__ . '/../../admin/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    die("Configuration file not found. Please check your installation.");
}

// Function to check if a table exists
function tableExists($conn, $tableName) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return ($result->rowCount() > 0);
    } catch(Exception $e) {
        return false;
    }
}

try {
    // Check if database exists, if not create it
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Connect to the specific database
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check if admin_users table exists - important for login page
    if (!tableExists($conn, 'admin_users')) {
        // Redirect to setup page if on admin pages
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        if ($currentScript !== 'db_setup.php' && strpos($currentScript, 'admin') !== false) {
            // Only redirect if we're on an admin page (not on db_setup)
            header('Location: db_setup.php');
            exit;
        }
    }
    
} catch(PDOException $e) {
    // For admin interfaces, show a nice error
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px; border: 1px solid #f5c6cb;">';
        echo '<h3>Database Connection Error</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<p>Please make sure your database is set up correctly and <a href="db_setup.php">run the setup script</a>.</p>';
        echo '</div>';
        exit;
    }
    
    // For public pages, show a generic error
    die("We're experiencing technical difficulties. Please try again later.");
}
?>