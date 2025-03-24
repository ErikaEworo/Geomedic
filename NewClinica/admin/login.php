<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Check database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin_users table exists
    $tableExists = false;
    $stmt = $conn->prepare("SHOW TABLES LIKE 'admin_users'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
    }
} catch(PDOException $e) {
    $dbError = "Database connection error: " . $e->getMessage();
}

$error = '';
$setupMessage = '';

// Process setup request
if (isset($_POST['setup_database'])) {
    try {
        // Redirect to db_setup.php
        header('Location: db_setup.php');
        exit;
    } catch(Exception $e) {
        $error = "Error setting up database: " . $e->getMessage();
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']) && $tableExists) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingrese usuario y contraseña';
    } else {
        try {
            // Check user in database
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Clínica Médica</h2>
                <p>Panel de Administración</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($setupMessage)): ?>
            <div class="alert alert-success"><?php echo $setupMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($dbError)): ?>
            <div class="alert alert-warning">
                <?php echo $dbError; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$tableExists): ?>
            <div class="alert alert-warning">
                <p>No se encontraron las tablas de la base de datos. Por favor, configure la base de datos primero.</p>
                <form method="POST" action="">
                    <button type="submit" name="setup_database" class="btn btn-info w-100 mt-2">Configurar Base de Datos</button>
                </form>
            </div>
            <?php else: ?>
            <form method="POST" action="login.php" class="login-form">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Clínica Médica. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>