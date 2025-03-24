<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Check if the contact_messages table exists
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    if ($tableCheck->rowCount() == 0) {
        // Create the table if it doesn't exist
        $createTable = "CREATE TABLE contact_messages (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0
        )";
        $conn->exec($createTable);
        $success_message = "Tabla de mensajes creada correctamente.";
    }
} catch(PDOException $e) {
    $error_message = "Error verificando o creando tabla: " . $e->getMessage();
}

// Process message operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete message
    if (isset($_POST['delete_message']) && isset($_POST['message_id'])) {
        $id = (int)$_POST['message_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $success_message = "Mensaje eliminado correctamente.";
        } catch(PDOException $e) {
            $error_message = "Error al eliminar el mensaje: " . $e->getMessage();
        }
    }
    
    // Create new message
    if (isset($_POST['add_message'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $subject = trim($_POST['subject']);
        $message_content = trim($_POST['message']);
        
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message_content);
            $stmt->execute();
            
            $success_message = "Mensaje creado correctamente.";
        } catch(PDOException $e) {
            $error_message = "Error al crear el mensaje: " . $e->getMessage();
        }
    }
    
    // Mark messages as read (bulk action)
    if (isset($_POST['mark_read_selected']) && isset($_POST['selected_messages'])) {
        $selected = $_POST['selected_messages'];
        if (!empty($selected)) {
            try {
                $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id IN ($placeholders)");
                $stmt->execute($selected);
                $success_message = count($selected) . " mensaje(s) marcado(s) como leído(s).";
            } catch(PDOException $e) {
                $error_message = "Error al actualizar los mensajes: " . $e->getMessage();
            }
        }
    }
    
    // Delete selected messages (bulk action)
    if (isset($_POST['delete_selected']) && isset($_POST['selected_messages'])) {
        $selected = $_POST['selected_messages'];
        if (!empty($selected)) {
            try {
                $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
                $stmt->execute($selected);
                $success_message = count($selected) . " mensaje(s) eliminado(s) correctamente.";
            } catch(PDOException $e) {
                $error_message = "Error al eliminar los mensajes: " . $e->getMessage();
            }
        }
    }
}

// Mark message as read (GET method still supported for backward compatibility)
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Mensaje marcado como leído.";
    } catch(PDOException $e) {
        $error_message = "Error al actualizar el mensaje: " . $e->getMessage();
    }
}

// Filter messages
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get messages with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count with filters
    $countQuery = "SELECT COUNT(*) FROM contact_messages WHERE 1=1";
    $countParams = [];
    
    if ($filter === 'unread') {
        $countQuery .= " AND is_read = 0";
    } else if ($filter === 'read') {
        $countQuery .= " AND is_read = 1";
    }
    
    if (!empty($search)) {
        $countQuery .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $countParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($countParams);
    $total_messages = $stmt->fetchColumn();
    $total_pages = ceil($total_messages / $limit);
    
    // Get messages for current page with filters
    $query = "SELECT * FROM contact_messages WHERE 1=1";
    $params = [];
    
    if ($filter === 'unread') {
        $query .= " AND is_read = 0";
    } else if ($filter === 'read') {
        $query .= " AND is_read = 1";
    }
    
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for the summary
    $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages");
    $total_all_messages = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
    $total_unread_messages = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 1");
    $total_read_messages = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $error_message = "Error al obtener los mensajes: " . $e->getMessage();
    $messages = [];
    $total_pages = 0;
    $total_all_messages = 0;
    $total_unread_messages = 0;
    $total_read_messages = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - Clínica Médica</title>
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
                                    <h2>Mensajes de Contacto</h2>
                                    <p>Administra los mensajes recibidos a través del formulario de contacto</p>
                                </div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMessageModal">
                                    <i class="fas fa-plus-circle me-2"></i>Nuevo Mensaje
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
                    
                    <!-- Messages Summary -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="stats-card bg-primary text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_all_messages; ?></div>
                                    <div class="stats-card-title">Total Mensajes</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-warning text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_unread_messages; ?></div>
                                    <div class="stats-card-title">Mensajes Sin Leer</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-envelope-open"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-success text-white">
                                <div class="stats-card-body">
                                    <div class="stats-card-value"><?php echo $total_read_messages; ?></div>
                                    <div class="stats-card-title">Mensajes Leídos</div>
                                </div>
                                <div class="stats-card-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Filters -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-body p-3">
                                    <form action="messages.php" method="GET" class="row g-3">
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Buscar mensajes..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="filter" onchange="this.form.submit()">
                                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Todos los mensajes</option>
                                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Sin leer</option>
                                                <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Leídos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <a href="messages.php" class="btn btn-secondary w-100">Reiniciar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages List -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>Mensajes</h4>
                                </div>
                                <div class="content-card-body">
                                    <form method="POST" action="messages.php" id="messagesForm">
                                        <?php if (count($messages) > 0): ?>
                                        <div class="mb-3">
                                            <button type="submit" name="mark_read_selected" class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-check me-1"></i> Marcar como leídos
                                            </button>
                                            <button type="submit" name="delete_selected" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar los mensajes seleccionados?')">
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
                                                        <th>Asunto</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($messages) > 0): ?>
                                                        <?php foreach ($messages as $message): ?>
                                                            <tr class="<?php echo $message['is_read'] ? '' : 'table-primary'; ?>">
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input message-checkbox" type="checkbox" name="selected_messages[]" value="<?php echo $message['id']; ?>">
                                                                    </div>
                                                                </td>
                                                                <td><?php echo $message['id']; ?></td>
                                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                                                <td><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></td>
                                                                <td>
                                                                    <?php if ($message['is_read']): ?>
                                                                        <span class="badge bg-success">Leído</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-primary">Nuevo</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info" title="Ver mensaje">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <?php if (!$message['is_read']): ?>
                                                                    <a href="messages.php?action=mark_read&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-success" title="Marcar como leído">
                                                                        <i class="fas fa-check"></i>
                                                                    </a>
                                                                    <?php endif; ?>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-message-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteMessageModal" 
                                                                        data-message-id="<?php echo $message['id']; ?>"
                                                                        data-message-name="<?php echo htmlspecialchars($message['name']); ?>"
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
                                                                    No se encontraron mensajes con los filtros aplicados.
                                                                    <a href="messages.php">Mostrar todos los mensajes</a>
                                                                <?php else: ?>
                                                                    No hay mensajes disponibles. Los mensajes aparecerán aquí cuando los visitantes envíen el formulario de contacto.
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
                                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : 'messages.php?page='.($page-1).'&filter='.$filter.'&search='.urlencode($search); ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="messages.php?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : 'messages.php?page='.($page+1).'&filter='.$filter.'&search='.urlencode($search); ?>" aria-label="Next">
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

    <!-- Delete Message Modal -->
    <div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-labelledby="deleteMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMessageModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar el mensaje de <span id="messageNameDisplay"></span>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="messages.php">
                        <input type="hidden" name="message_id" id="deleteMessageId">
                        <button type="submit" name="delete_message" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Message Modal -->
    <div class="modal fade" id="addMessageModal" tabindex="-1" aria-labelledby="addMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMessageModalLabel">Crear Nuevo Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="messages.php">
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
                            <label for="phone" class="form-label">Teléfono (opcional)</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Asunto</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_message" class="btn btn-primary">Crear Mensaje</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete message modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Delete message modal functionality
            const deleteButtons = document.querySelectorAll('.delete-message-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const messageId = this.getAttribute('data-message-id');
                    const messageName = this.getAttribute('data-message-name');
                    document.getElementById('deleteMessageId').value = messageId;
                    document.getElementById('messageNameDisplay').textContent = messageName;
                });
            });
            
            // Select all checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const messageCheckboxes = document.querySelectorAll('.message-checkbox');
                    messageCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
            }
        });
    </script>
</body>
</html>