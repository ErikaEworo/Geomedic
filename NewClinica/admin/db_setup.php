<?php
// Add styling for better presentation
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .setup-container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #0d6efd; }
        .success { color: green; }
        .error { color: red; }
        .progress-bar { height: 20px; background-color: #e9ecef; border-radius: 5px; margin: 20px 0; overflow: hidden; }
        .progress { height: 100%; background-color: #0d6efd; width: 0%; transition: width 0.5s; }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Database Setup</h1>
        <div class="progress-bar">
            <div class="progress" id="progress"></div>
        </div>
        <div id="setup-log">';

require_once 'config.php';

echo "<p>Starting database setup process...</p>";

// Update progress
echo '<script>document.getElementById("progress").style.width = "10%";</script>';
flush();

try {
    // Connect to server
    echo "<p>Connecting to database server...</p>";
    $conn = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    echo "<p>Creating database if it doesn't exist...</p>";
    $conn->exec("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE ".DB_NAME);
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "20%";</script>';
    flush();
    
    // Create tables
    echo "<p>Creating tables...</p>";
    
    // 1. Admin Users table
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'admin_users' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "30%";</script>';
    flush();
    
    // 2. Contact Messages table
    $conn->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'contact_messages' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "40%";</script>';
    flush();
    
    // 3. Services table
    $conn->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        subtitle VARCHAR(150) NULL,
        description TEXT NOT NULL,
        icon VARCHAR(50) NULL,
        image VARCHAR(255) NULL,
        is_featured BOOLEAN DEFAULT 0,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'services' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "50%";</script>';
    flush();
    
    // 4. Specialists table
    $conn->exec("CREATE TABLE IF NOT EXISTS specialists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        specialty VARCHAR(100) NOT NULL,
        bio TEXT NOT NULL,
        image VARCHAR(255) NULL,
        email VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        social_media JSON NULL,
        is_featured BOOLEAN DEFAULT 0,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'specialists' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "60%";</script>';
    flush();
    
    // 5. News table
    $conn->exec("CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        image VARCHAR(255) NULL,
        author VARCHAR(100) NULL,
        is_published BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        published_at TIMESTAMP NULL
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'news' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "70%";</script>';
    flush();
    
    // 6. Testimonials table
    $conn->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NULL,
        content TEXT NOT NULL,
        rating INT DEFAULT 5,
        image VARCHAR(255) NULL,
        is_approved BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'testimonials' created or already exists.</p>";
    
    // 7. Settings table
    $conn->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        setting_description VARCHAR(255) NULL,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'settings' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "80%";</script>';
    flush();
    
    // 8. Homepage Sections table
    $conn->exec("CREATE TABLE IF NOT EXISTS homepage_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(200) NOT NULL,
        subtitle TEXT NULL,
        content TEXT NULL,
        image VARCHAR(255) NULL,
        is_active BOOLEAN DEFAULT 1,
        order_num INT DEFAULT 0,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<p class='success'>- Table 'homepage_sections' created or already exists.</p>";
    
    // Update progress
    echo '<script>document.getElementById("progress").style.width = "90%";</script>';
    flush();
    
    // Insert default admin user if not exists
    echo "<p>Setting up default data...</p>";
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = (int)$stmt->fetchColumn();
        
        if ($adminExists === 0) {
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, full_name) VALUES ('admin', :password, 'admin@clinicamedica.com', 'Administrator')");
            $stmt->bindParam(':password', $defaultPassword);
            $stmt->execute();
            echo "<p class='success'>- Default admin user created. Username: 'admin', Password: 'admin123'</p>";
        } else {
            echo "<p>- Admin user already exists.</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>Error setting up admin user: " . $e->getMessage() . "</p>";
    }
    
    // Insert default settings
    try {
        $defaultSettings = [
            ['site_name', 'Clínica Médica', 'Website name'],
            ['contact_email', 'info@clinicamedica.com', 'Contact email address'],
            ['contact_phone', '+1234567890', 'Contact phone number'],
            ['contact_address', '123 Medical St, Healthcare City', 'Physical address'],
            ['social_facebook', 'https://facebook.com/clinicamedica', 'Facebook URL'],
            ['social_twitter', 'https://twitter.com/clinicamedica', 'Twitter URL'],
            ['social_instagram', 'https://instagram.com/clinicamedica', 'Instagram URL']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_description) VALUES (:key, :value, :description)");
            $stmt->bindParam(':key', $setting[0]);
            $stmt->bindParam(':value', $setting[1]);
            $stmt->bindParam(':description', $setting[2]);
            $stmt->execute();
        }
        echo "<p class='success'>- Default settings inserted.</p>";
    } catch(PDOException $e) {
        echo "<p class='error'>Error setting up default settings: " . $e->getMessage() . "</p>";
    }
    
    // Insert default homepage sections
    try {
        $defaultSections = [
            ['hero', 'Bienvenido a Nuestra Clínica', 'Cuidado médico de calidad para ti y tu familia', null, 'hero.jpg', 1, 1],
            ['servicios', 'Nuestros Servicios', 'Ofrecemos una amplia gama de servicios médicos especializados para cuidar de tu salud', null, null, 1, 2],
            ['about', 'Sobre Nosotros', 'Conoce más sobre nuestra clínica y nuestro compromiso con tu salud', 'Somos una clínica médica dedicada a proporcionar atención médica de alta calidad. Nuestro equipo de médicos altamente calificados está comprometido con el bienestar de nuestros pacientes.', 'about.jpg', 1, 3],
            ['specialists', 'Nuestros Especialistas', 'Contamos con un equipo de médicos especializados para atender todas tus necesidades', null, null, 1, 4],
            ['testimonials', 'Testimonios', 'Lo que nuestros pacientes dicen sobre nosotros', null, null, 1, 5],
            ['news', 'Noticias y Artículos', 'Mantente informado con nuestras últimas noticias y artículos médicos', null, null, 1, 6],
            ['contact', 'Contáctanos', 'Estamos aquí para ayudarte', null, 'contact.jpg', 1, 7]
        ];
        
        foreach ($defaultSections as $section) {
            $stmt = $conn->prepare("INSERT IGNORE INTO homepage_sections (section_name, title, subtitle, content, image, is_active, order_num) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($section);
        }
        echo "<p class='success'>- Default homepage sections inserted.</p>";
    } catch(PDOException $e) {
        echo "<p class='error'>Error setting up homepage sections: " . $e->getMessage() . "</p>";
    }
    
    // Complete progress
    echo '<script>document.getElementById("progress").style.width = "100%";</script>';
    flush();
    
    echo "<p class='success'><strong>Database setup completed successfully!</strong></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo '</div>
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">Go to Admin Login</a>
            <a href="../index.php" class="btn btn-secondary ms-2">Go to Homepage</a>
            <a href="db_reset.php" class="btn btn-danger ms-2">Reset Database (Caution!)</a>
        </div>
    </div>
    
    <script>
        // Scroll to bottom automatically
        window.onload = function() {
            window.scrollTo(0, document.body.scrollHeight);
        };
    </script>
</body>
</html>';
?>