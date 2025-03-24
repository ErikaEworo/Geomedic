<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// Include database configuration
if (file_exists('admin/config.php')) {
    include_once 'admin/config.php';
    echo "<p>✅ Config file found and included</p>";
} else {
    echo "<p>❌ Config file not found!</p>";
    die("Please check that admin/config.php exists");
}

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<p>❌ Connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p>✅ Database connection successful</p>";
        
        // Check tables
        echo "<h2>Checking Required Tables</h2>";
        $tables = ["homepage_sections", "services", "specialists", "news", "site_settings"];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '{$table}'");
            if ($result && $result->num_rows > 0) {
                echo "<p>✅ Table '{$table}' exists</p>";
                
                // Count records
                $count = $conn->query("SELECT COUNT(*) as total FROM {$table}");
                $countData = $count->fetch_assoc();
                echo "<p>&nbsp;&nbsp;&nbsp;└── Contains {$countData['total']} records</p>";
            } else {
                echo "<p>❌ Table '{$table}' does not exist!</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . " seconds</p>";
?> 