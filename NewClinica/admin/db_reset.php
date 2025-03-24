<?php
// This file is for resetting admin users in the database
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->exec("TRUNCATE TABLE admin_users");
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f8f9fa; border-radius: 5px;'>";
    echo "<h2 style='color: #28a745;'>Admin Users Reset Successfully</h2>";
    echo "<p>All admin users have been removed from the database.</p>";
    echo "<p>You can now <a href='signup.php' style='color: #007bff; text-decoration: none;'>create a new admin account</a>.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f8d7da; border-radius: 5px; color: #721c24;'>";
    echo "<h2>Error</h2>";
    echo "<p>Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure your database configuration is correct in config.php.</p>";
    echo "</div>";
}
?>