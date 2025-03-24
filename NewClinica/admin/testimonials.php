<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

// First, check if the is_approved column exists, if not add it
try {
    $checkColumn = $conn->query("SHOW COLUMNS FROM testimonials LIKE 'is_approved'");
    if ($checkColumn->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE testimonials ADD COLUMN is_approved BOOLEAN DEFAULT 0");
        echo '<div class="alert alert-info">Database updated: Added missing column "is_approved" to testimonials table.</div>';
    }
} catch (PDOException $e) {
    $error_message = "Error checking table structure: " . $e->getMessage();
}

// Approve or disapprove testimonial
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $id = $_GET['approve'];
    try {
        $stmt = $conn->prepare("UPDATE testimonials SET is_approved = NOT is_approved WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Estado del testimonio actualizado correctamente.";
    } catch (PDOException $e) {
        $error_message = "Error al actualizar el estado del testimonio: " . $e->getMessage();
    }
}

// Delete testimonial
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Check if image exists and delete it
        $stmt = $conn->prepare("SELECT image FROM testimonials WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $image = $stmt->fetchColumn();
        
        if ($image && file_exists('../img/testimonials/' . $image)) {
            unlink('../img/testimonials/' . $image);
        }
        
        // Delete testimonial
        $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Testimonio eliminado correctamente.";
    } catch (PDOException $e) {
        $error_message = "Error al eliminar el testimonio: " . $e->getMessage();
    }
}

// Get all testimonials
try {
    $stmt = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
    $testimonials = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al obtener los testimonios: " . $e->getMessage();
    $testimonials = [];
}

// Handling the form submission for adding or editing a testimonial
$editing = false;
$testimonial = [
    'id' => '',
    'name' => '',
    'position' => '',
    'content' => '',
    'rating' => 5,
    'is_approved' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;
    
    // Validate input
    if (empty($name) || empty($content)) {
        $error_message = "Por favor, complete todos los campos obligatorios.";
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = "La calificación debe estar entre 1 y 5.";
    } else {
        // Handle image upload
        $image = '';
        $update_image = false;
        
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../img/testimonials/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is an actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $error_message = "El archivo no es una imagen válida.";
            }
            // Check file size (limit to 2MB)
            elseif ($_FILES["image"]["size"] > 2000000) {
                $error_message = "El archivo es demasiado grande. Máximo 2MB.";
            }
            // Allow certain file formats
            elseif ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                $error_message = "Solo se permiten archivos JPG, JPEG, PNG.";
            }
            // Try to upload file
            elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $update_image = true;
            } else {
                $error_message = "Error al subir la imagen.";
            }
        }
        
        if (!isset($error_message)) {
            try {
                if (!empty($id)) {
                    // Update existing testimonial
                    if ($update_image) {
                        // Delete old image if exists
                        $stmt = $conn->prepare("SELECT image FROM testimonials WHERE id = :id");
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                        $old_image = $stmt->fetchColumn();
                        
                        if ($old_image && file_exists('../img/testimonials/' . $old_image)) {
                            unlink('../img/testimonials/' . $old_image);
                        }
                        
                        $stmt = $conn->prepare("UPDATE testimonials SET name = :name, position = :position, 
                                             content = :content, rating = :rating, image = :image, 
                                             is_approved = :is_approved WHERE id = :id");
                        $stmt->bindParam(':image', $image);
                    } else {
                        $stmt = $conn->prepare("UPDATE testimonials SET name = :name, position = :position, 
                                             content = :content, rating = :rating, 
                                             is_approved = :is_approved WHERE id = :id");
                    }
                    $stmt->bindParam(':id', $id);
                    $success_message = "Testimonio actualizado correctamente.";
                } else {
                    // Add new testimonial
                    if ($update_image) {
                        $stmt = $conn->prepare("INSERT INTO testimonials (name, position, content, rating, image, is_approved) 
                                             VALUES (:name, :position, :content, :rating, :image, :is_approved)");
                        $stmt->bindParam(':image', $image);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO testimonials (name, position, content, rating, is_approved) 
                                             VALUES (:name, :position, :content, :rating, :is_approved)");
                    }
                    $success_message = "Testimonio agregado correctamente.";
                }
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':rating', $rating);
                $stmt->bindParam(':is_approved', $is_approved);
                $stmt->execute();
                
                // Redirect to clear the form
                header('Location: testimonials.php?success=' . urlencode($success_message));
                exit;
            } catch (PDOException $e) {
                $error_message = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
} 
// Edit testimonial
elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $editing = true;
            $testimonial = $result;
        }
    } catch (PDOException $e) {
        $error_message = "Error al obtener datos del testimonio: " . $e->getMessage();
    }
}

// Get success message from URL if it exists
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Testimonios - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/topnav.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $editing ? 'Editar Testimonio' : 'Gestionar Testimonios'; ?></h1>
                    <?php if ($editing): ?>
                    <a href="testimonials.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-comment me-1"></i>
                        <?php echo $editing ? 'Editar Testimonio' : 'Agregar Nuevo Testimonio'; ?>
                    </div>
                    <div class="card-body">
                        <form action="testimonials.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($testimonial['id']); ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($testimonial['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="position" class="form-label">Posición/Cargo</label>
                                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($testimonial['position'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Testimonio <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($testimonial['content'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="rating" class="form-label">Calificación</label>
                                    <select class="form-select" id="rating" name="rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (($testimonial['rating'] ?? 5) == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> <?php echo ($i > 1) ? 'estrellas' : 'estrella'; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="image" class="form-label">Imagen</label>
                                    <?php if (!empty($testimonial['image']) && file_exists('../img/testimonials/' . $testimonial['image'])): ?>
                                    <div class="mb-2">
                                        <img src="../img/testimonials/<?php echo htmlspecialchars($testimonial['image']); ?>" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="image" name="image">
                                    <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB.</small>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_approved" name="is_approved" <?php echo ($testimonial['is_approved'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_approved">Aprobar para mostrar en el sitio</label>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    <?php echo $editing ? 'Actualizar Testimonio' : 'Agregar Testimonio'; ?>
                                </button>
                                <?php if ($editing): ?>
                                <a href="testimonials.php" class="btn btn-secondary">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!$editing): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-1"></i>
                        Lista de Testimonios
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Valoración</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($testimonials)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay testimonios registrados.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($testimonials as $test): ?>
                                    <tr>
                                        <td><?php echo $test['id']; ?></td>
                                        <td>
                                            <?php if (!empty($test['image']) && file_exists('../img/testimonials/' . $test['image'])): ?>
                                                <img src="../img/testimonials/<?php echo htmlspecialchars($test['image']); ?>" alt="<?php echo htmlspecialchars($test['name']); ?>" class="img-thumbnail" style="max-height: 50px;">
                                            <?php else: ?>
                                                <span class="text-muted">Sin imagen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['name']); ?></td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo ($i <= $test['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                                        <td>
                                            <a href="testimonials.php?approve=<?php echo $test['id']; ?>" class="btn btn-sm <?php echo isset($test['is_approved']) && $test['is_approved'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo isset($test['is_approved']) && $test['is_approved'] ? 'Aprobado' : 'Pendiente'; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="testimonials.php?edit=<?php echo $test['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="testimonials.php?delete=<?php echo $test['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este testimonio?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>