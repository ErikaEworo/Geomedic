<?php
session_start();

require_once 'includes/db_connect.php';

// Check if any admin users exist
$stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users");
$stmt->execute();
$adminCount = (int)$stmt->fetchColumn();

// If admin users exist, require authentication
$requireAuth = ($adminCount > 0);

// Check authentication if required
if ($requireAuth && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Process signup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');

    // Validate inputs
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'El nombre de usuario o correo electrónico ya está en uso';
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, full_name) VALUES (:username, :password, :email, :full_name)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->execute();
                
                if (!$requireAuth) {
                    // If this is the first admin, log them in automatically
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $conn->lastInsertId();
                    $_SESSION['admin_username'] = $username;
                    
                    $success = 'Cuenta de administrador creada exitosamente. Ya estás conectado.';
                } else {
                    $success = 'El administrador se ha creado exitosamente';
                }
            } catch (PDOException $e) {
                $error = 'Error al crear el usuario: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Administrador - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php if ($requireAuth): ?>
    <?php include 'includes/topnav.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <?php else: ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <main class="col-md-8">
    <?php endif; ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $requireAuth ? 'Crear Nuevo Administrador' : 'Configurar Primer Administrador'; ?></h1>
                </div>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <?php if (!$requireAuth): ?>
                    <br><a href="index.php" class="alert-link">Ir al Panel de Administración</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (!$requireAuth): ?>
                        <div class="alert alert-info">
                            <strong>¡Bienvenido!</strong> No se ha detectado ninguna cuenta de administrador. Crea la primera cuenta para acceder al panel de administración.
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="signup.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">La contraseña debe tener al menos 8 caracteres.</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $requireAuth ? 'Crear Administrador' : 'Crear Cuenta de Administrador'; ?>
                            </button>
                            <?php if ($requireAuth): ?>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>