<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Check if the users table exists
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        // Create the table if it doesn't exist
        $createTable = "CREATE TABLE users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status TINYINT(1) DEFAULT 1,
            role VARCHAR(20) DEFAULT 'client'
        )";
        $conn->exec($createTable);
        $success_message = "Tabla de usuarios creada correctamente.";
    }
} catch(PDOException $e) {
    $error_message = "Error verificando o creando tabla: " . $e->getMessage();
}

// Process user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete user
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $id = (int)$_POST['user_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'client'");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $success_message = "Usuario eliminado correctamente.";
        } catch(PDOException $e) {
            $error_message = "Error al eliminar el usuario: " . $e->getMessage();
        }
    }
    
    // Update user status (activate/deactivate)
    if (isset($_POST['update_status']) && isset($_POST['user_id']) && isset($_POST['status'])) {
        $id = (int)$_POST['user_id'];
        $status = (int)$_POST['status'];
        try {
            $stmt = $conn->prepare("UPDATE users SET status = :status WHERE id = :id AND role = 'client'");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $success_message = "Estado del usuario actualizado correctamente.";
        } catch(PDOException $e) {
            $error_message = "Error al actualizar el estado del usuario: " . $e->getMessage();
        }
    }
    
    // Update user
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        
        try {
            // Check if email exists and belongs to another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_message = "El correo electrónico ya está en uso por otro usuario.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id AND role = 'client'");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                $success_message = "Usuario actualizado correctamente.";
            }
        } catch(PDOException $e) {
            $error_message = "Error al actualizar el usuario: " . $e->getMessage();
        }
    }
    
    // Create new user
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_message = "El correo electrónico ya está en uso.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (:name, :email, :password, :phone, :address, 'client')");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->execute();
                
                $success_message = "Usuario creado correctamente.";
            }
        } catch(PDOException $e) {
            $error_message = "Error al crear el usuario: " . $e->getMessage();
        }
    }
    
    // Delete selected users (bulk action)
    if (isset($_POST['delete_selected']) && isset($_POST['selected_users'])) {
        $selected = $_POST['selected_users'];
        if (!empty($selected)) {
            try {
                $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role = 'client'");
                $stmt->execute($selected);
                $success_message = count($selected) . " usuario(s) eliminado(s) correctamente.";
            } catch(PDOException $e) {
                $error_message = "Error al eliminar los usuarios: " . $e->getMessage();
            }
        }
    }
}

// Filter users
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count with filters
    $countQuery = "SELECT COUNT(*) FROM users WHERE role = 'client'";
    $countParams = [];
    
    if ($filter === 'active') {
        $countQuery .= " AND status = 1";
    } else if ($filter === 'inactive') {
        $countQuery .= " AND status = 0";
    }
    
    if (!empty($search)) {
        $countQuery .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = "%$search%";
        $countParams = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($countParams);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
    
    // Get users for current page with filters
    $query = "SELECT * FROM users WHERE role = 'client'";
    $params = [];
    
    if ($filter === 'active') {
        $query .= " AND status = 1";
    } else if ($filter === 'inactive') {
        $query .= " AND status = 0";
    }
    
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for the summary
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
    $total_all_users = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND status = 1");
    $total_active_users = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND status = 0");
    $total_inactive_users = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $error_message = "Error al obtener los usuarios: " . $e->getMessage();
    $users = [];
    $total_pages = 0;
    $total_all_users = 0;
    $total_active_users = 0;
    $total_inactive_users = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Clínica Médica</title>
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
                            <div class="dashboard-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h2>Gestión de Usuarios</h2>
                                    <p>Administra las cuentas de clientes registrados en el sistema</p>
                                </div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Users Summary -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="stats-card bg-primary text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_all_users; ?></div>
                                    <div class="stats-card-title">Total Usuarios</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-success text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_active_users; ?></div>
                                    <div class="stats-card-title">Usuarios Activos</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-danger text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_inactive_users; ?></div>
                                    <div class="stats-card-title">Usuarios Inactivos</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Filters -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-body p-3">
                                    <form action="users.php" method="GET" class="row g-3">
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Buscar usuarios..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="filter" onchange="this.form.submit()">
                                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Todos los usuarios</option>
                                                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Usuarios activos</option>
                                                <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Usuarios inactivos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <a href="users.php" class="btn btn-secondary w-100">Reiniciar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>Usuarios Registrados</h4>
                                </div>
                                <div class="content-card-body">
                                    <form method="POST" action="users.php" id="usersForm">
                                        <?php if (count($users) > 0): ?>
                                        <div class="mb-3">
                                            <button type="submit" name="delete_selected" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar los usuarios seleccionados?')">
                                                <i class="fas fa-trash me-1"></i> Eliminar seleccionados
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                                            </div>
                                                        </th>
                                                        <th>ID</th>
                                                        <th>Nombre</th>
                                                        <th>Email</th>
                                                        <th>Teléfono</th>
                                                        <th>Fecha Registro</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($users) > 0): ?>
                                                        <?php foreach ($users as $user): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input user-checkbox" type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>">
                                                                    </div>
                                                                </td>
                                                                <td><?php echo $user['id']; ?></td>
                                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['phone'] ?? 'No disponible'); ?></td>
                                                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                                                <td>
                                                                    <?php if ($user['status'] == 1): ?>
                                                                        <span class="badge bg-success">Activo</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger">Inactivo</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-primary edit-user-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#editUserModal" 
                                                                        data-user-id="<?php echo $user['id']; ?>"
                                                                        data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                                        data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                        data-user-phone="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                                                        data-user-address="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                                                        title="Editar">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <?php if ($user['status'] == 1): ?>
                                                                    <form method="POST" style="display: inline-block;">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                        <input type="hidden" name="status" value="0">
                                                                        <button type="submit" name="update_status" class="btn btn-sm btn-warning" title="Desactivar">
                                                                            <i class="fas fa-user-slash"></i>
                                                                        </button>
                                                                    </form>
                                                                    <?php else: ?>
                                                                    <form method="POST" style="display: inline-block;">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                        <input type="hidden" name="status" value="1">
                                                                        <button type="submit" name="update_status" class="btn btn-sm btn-success" title="Activar">
                                                                            <i class="fas fa-user-check"></i>
                                                                        </button>
                                                                    </form>
                                                                    <?php endif; ?>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-user-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteUserModal" 
                                                                        data-user-id="<?php echo $user['id']; ?>"
                                                                        data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                                        title="Eliminar">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">
                                                                <?php if (!empty($search) || $filter !== 'all'): ?>
                                                                    No se encontraron usuarios con los filtros aplicados.
                                                                    <a href="users.php">Mostrar todos los usuarios</a>
                                                                <?php else: ?>
                                                                    No hay usuarios registrados. Los usuarios aparecerán aquí cuando se registren en el sitio.
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>

                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : 'users.php?page='.($page-1).'&filter='.$filter.'&search='.urlencode($search); ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="users.php?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : 'users.php?page='.($page+1).'&filter='.$filter.'&search='.urlencode($search); ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar al usuario <span id="userNameDisplay"></span>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="users.php">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" name="delete_user" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="users.php">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="mb-3">
                            <label for="editName" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="editPhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Dirección</label>
                            <textarea class="form-control" id="editAddress" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="users.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete user modal functionality
            const deleteButtons = document.querySelectorAll('.delete-user-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    document.getElementById('deleteUserId').value = userId;
                    document.getElementById('userNameDisplay').textContent = userName;
                });
            });
            
            // Edit user modal functionality
            const editButtons = document.querySelectorAll('.edit-user-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    const userEmail = this.getAttribute('data-user-email');
                    const userPhone = this.getAttribute('data-user-phone');
                    const userAddress = this.getAttribute('data-user-address');
                    
                    document.getElementById('editUserId').value = userId;
                    document.getElementById('editName').value = userName;
                    document.getElementById('editEmail').value = userEmail;
                    document.getElementById('editPhone').value = userPhone;
                    document.getElementById('editAddress').value = userAddress;
                });
            });
            
            // Select all checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const userCheckboxes = document.querySelectorAll('.user-checkbox');
                    userCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
            }
        });
    </script>
</body>
</html> 