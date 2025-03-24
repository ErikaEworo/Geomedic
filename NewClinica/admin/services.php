<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Process service deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM services WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Servicio eliminado correctamente.";
    } catch(PDOException $e) {
        $error_message = "Error al eliminar el servicio: " . $e->getMessage();
    }
}

// Toggle featured status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_featured' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE services SET is_featured = NOT is_featured WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Estado destacado actualizado correctamente.";
    } catch(PDOException $e) {
        $error_message = "Error al actualizar el estado: " . $e->getMessage();
    }
}

// Toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_active' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE services SET is_active = NOT is_active WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Estado activo actualizado correctamente.";
    } catch(PDOException $e) {
        $error_message = "Error al actualizar el estado: " . $e->getMessage();
    }
}

// Get services with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM services");
    $stmt->execute();
    $total_services = $stmt->fetchColumn();
    $total_pages = ceil($total_services / $limit);
    
    // Get services for current page
    $stmt = $conn->prepare("SELECT * FROM services ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error al obtener los servicios: " . $e->getMessage();
    $services = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Clínica Médica</title>
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
                        <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                            <div class="dashboard-header">
                                <h2>Servicios</h2>
                                <p>Administra los servicios ofrecidos por la clínica</p>
                            </div>
                            <a href="add_service.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Nuevo Servicio
                            </a>
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

                    <!-- Services List -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>Lista de Servicios</h4>
                                </div>
                                <div class="content-card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Imagen</th>
                                                    <th>Título</th>
                                                    <th>Subtítulo</th>
                                                    <th>Destacado</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($services) > 0): ?>
                                                    <?php foreach ($services as $service): ?>
                                                        <tr>
                                                            <td><?php echo $service['id']; ?></td>
                                                            <td>
                                                                <?php if (!empty($service['image'])): ?>
                                                                    <img src="../img/services/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                                                <?php else: ?>
                                                                    <span class="text-muted"><i class="fas fa-image"></i> Sin imagen</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($service['title']); ?></td>
                                                            <td><?php echo htmlspecialchars($service['subtitle']); ?></td>
                                                            <td>
                                                                <a href="services.php?action=toggle_featured&id=<?php echo $service['id']; ?>" class="badge bg-<?php echo $service['is_featured'] ? 'warning' : 'secondary'; ?> text-decoration-none">
                                                                    <?php echo $service['is_featured'] ? '<i class="fas fa-star"></i> Destacado' : '<i class="far fa-star"></i> Normal'; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <a href="services.php?action=toggle_active&id=<?php echo $service['id']; ?>" class="badge bg-<?php echo $service['is_active'] ? 'success' : 'danger'; ?> text-decoration-none">
                                                                    <?php echo $service['is_active'] ? '<i class="fas fa-check"></i> Activo' : '<i class="fas fa-times"></i> Inactivo'; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo date('d/m/Y', strtotime($service['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <a href="add_service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-sm btn-danger delete-service" 
                                                                       data-bs-toggle="modal" 
                                                                       data-bs-target="#deleteServiceModal" 
                                                                       data-id="<?php echo $service['id']; ?>" 
                                                                       data-title="<?php echo htmlspecialchars($service['title']); ?>"
                                                                       title="Eliminar">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">No hay servicios disponibles.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : 'services.php?page='.($page-1); ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="services.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : 'services.php?page='.($page+1); ?>" aria-label="Next">
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
    
    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteServiceModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el servicio <strong id="service-title"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handler for delete confirmation modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteServiceModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    // Button that triggered the modal
                    const button = event.relatedTarget;
                    
                    // Extract info from data-* attributes
                    const serviceId = button.getAttribute('data-id');
                    const serviceTitle = button.getAttribute('data-title');
                    
                    // Update the modal's content
                    document.getElementById('service-title').textContent = serviceTitle;
                    document.getElementById('confirm-delete').href = 'services.php?action=delete&id=' + serviceId;
                });
            }
        });
    </script>
</body>
</html>