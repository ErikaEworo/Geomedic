<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="dashboard-header">
                                <h2>Dashboard</h2>
                                <p>Bienvenido al panel de administración</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-primary">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Mensajes</h5>
                                        <?php
                                        // Get total messages count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-success">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Especialistas</h5>
                                        <?php
                                        // Get total specialists count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM specialists");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-info">
                                        <i class="fas fa-stethoscope"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Servicios</h5>
                                        <?php
                                        // Get total services count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM services");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-warning">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Noticias</h5>
                                        <?php
                                        // Get total news count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM news");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Messages -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>Mensajes Recientes</h4>
                                    <a href="messages.php" class="btn btn-sm btn-primary">Ver Todos</a>
                                </div>
                                <div class="content-card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Asunto</th>
                                                    <th>Fecha</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get recent messages
                                                $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
                                                $stmt->execute();
                                                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                if (count($messages) > 0) {
                                                    foreach ($messages as $message) {
                                                        echo '<tr>';
                                                        echo '<td>' . htmlspecialchars($message['name']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($message['email']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($message['subject']) . '</td>';
                                                        echo '<td>' . date('d/m/Y', strtotime($message['created_at'])) . '</td>';
                                                        echo '<td>
                                                                <a href="view_message.php?id=' . $message['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                                <a href="delete_message.php?id=' . $message['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Estás seguro de eliminar este mensaje?\')"><i class="fas fa-trash"></i></a>
                                                              </td>';
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="5" class="text-center">No hay mensajes recientes</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-script.js"></script>
</body>
</html><?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="dashboard-header">
                                <h2>Dashboard</h2>
                                <p>Bienvenido al panel de administración</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-primary">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Mensajes</h5>
                                        <?php
                                        // Get total messages count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-success">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Especialistas</h5>
                                        <?php
                                        // Get total specialists count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM specialists");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-info">
                                        <i class="fas fa-stethoscope"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Servicios</h5>
                                        <?php
                                        // Get total services count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM services");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-warning">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <h5>Noticias</h5>
                                        <?php
                                        // Get total news count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM news");
                                        $stmt->execute();
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <h3><?php echo $result['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Messages -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>Mensajes Recientes</h4>
                                    <a href="messages.php" class="btn btn-sm btn-primary">Ver Todos</a>
                                </div>
                                <div class="content-card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Asunto</th>
                                                    <th>Fecha</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get recent messages
                                                $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
                                                $stmt->execute();
                                                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                if (count($messages) > 0) {
                                                    foreach ($messages as $message) {
                                                        echo '<tr>';
                                                        echo '<td>' . htmlspecialchars($message['name']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($message['email']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($message['subject']) . '</td>';
                                                        echo '<td>' . date('d/m/Y', strtotime($message['created_at'])) . '</td>';
                                                        echo '<td>
                                                                <a href="view_message.php?id=' . $message['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                                <a href="delete_message.php?id=' . $message['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Estás seguro de eliminar este mensaje?\')"><i class="fas fa-trash"></i></a>
                                                              </td>';
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="5" class="text-center">No hay mensajes recientes</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-script.js"></script>
</body>
</html>