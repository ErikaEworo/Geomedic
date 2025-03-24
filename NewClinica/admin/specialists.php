<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

// First, check if the required columns exist, if not add them
try {
    // Check for social_media column
    $checkSocialMedia = $conn->query("SHOW COLUMNS FROM specialists LIKE 'social_media'");
    if ($checkSocialMedia->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE specialists ADD COLUMN social_media JSON NULL");
        echo '<div class="alert alert-info">Database updated: Added missing column "social_media" to specialists table.</div>';
    }
    
    // Check for is_featured column
    $checkFeatured = $conn->query("SHOW COLUMNS FROM specialists LIKE 'is_featured'");
    if ($checkFeatured->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE specialists ADD COLUMN is_featured BOOLEAN DEFAULT 0");
        echo '<div class="alert alert-info">Database updated: Added missing column "is_featured" to specialists table.</div>';
    }
    
    // Check for is_active column
    $checkActive = $conn->query("SHOW COLUMNS FROM specialists LIKE 'is_active'");
    if ($checkActive->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE specialists ADD COLUMN is_active BOOLEAN DEFAULT 1");
        echo '<div class="alert alert-info">Database updated: Added missing column "is_active" to specialists table.</div>';
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error checking table structure: ' . $e->getMessage() . '</div>';
}

// Delete specialist
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Check if image exists and delete it
        $stmt = $conn->prepare("SELECT image FROM specialists WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $image = $stmt->fetchColumn();
        
        if ($image && file_exists('../img/specialists/' . $image)) {
            unlink('../img/specialists/' . $image);
        }
        
        // Delete specialist
        $stmt = $conn->prepare("DELETE FROM specialists WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Especialista eliminado correctamente.";
    } catch (PDOException $e) {
        $error_message = "Error al eliminar el especialista: " . $e->getMessage();
    }
}

// Toggle featured status
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    $id = $_GET['feature'];
    try {
        $stmt = $conn->prepare("UPDATE specialists SET is_featured = 1 - COALESCE(is_featured, 0) WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $success_message = "Estado destacado actualizado correctamente.";
    } catch (PDOException $e) {
        $error_message = "Error al actualizar el estado destacado: " . $e->getMessage();
    }
}

// Get all specialists
try {
    $stmt = $conn->query("SELECT * FROM specialists ORDER BY name ASC");
    $specialists = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al obtener los especialistas: " . $e->getMessage();
    $specialists = [];
}

// Handling the form submission for adding or editing a specialist
$editing = false;
$specialist = [
    'id' => '',
    'name' => '',
    'specialty' => '',
    'bio' => '',
    'email' => '',
    'phone' => '',
    'social_media' => '{"facebook":"","twitter":"","instagram":""}',
    'is_featured' => 0,
    'is_active' => 1
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $social_media = json_encode([
        'facebook' => $facebook,
        'twitter' => $twitter,
        'instagram' => $instagram
    ]);
    
    // Validate input
    if (empty($name) || empty($specialty) || empty($bio)) {
        $error_message = "Por favor, complete todos los campos obligatorios.";
    } else {
        // Handle image upload
        $image = '';
        $update_image = false;
        
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../img/specialists/";
            
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
                    // Update existing specialist
                    if ($update_image) {
                        // Delete old image if exists
                        $stmt = $conn->prepare("SELECT image FROM specialists WHERE id = :id");
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                        $old_image = $stmt->fetchColumn();
                        
                        if ($old_image && file_exists('../img/specialists/' . $old_image)) {
                            unlink('../img/specialists/' . $old_image);
                        }
                        
                        $stmt = $conn->prepare("UPDATE specialists SET name = :name, specialty = :specialty, 
                                              bio = :bio, image = :image, email = :email, phone = :phone, 
                                              social_media = :social_media, is_featured = :is_featured, 
                                              is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                        $stmt->bindParam(':image', $image);
                    } else {
                        $stmt = $conn->prepare("UPDATE specialists SET name = :name, specialty = :specialty, 
                                              bio = :bio, email = :email, phone = :phone, 
                                              social_media = :social_media, is_featured = :is_featured, 
                                              is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                    }
                    $stmt->bindParam(':id', $id);
                    $success_message = "Especialista actualizado correctamente.";
                } else {
                    // Add new specialist
                    if (!$update_image) {
                        $image = 'default.jpg'; // Default image if none uploaded
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO specialists (name, specialty, bio, image, email, phone, 
                                          social_media, is_featured, is_active) VALUES (:name, :specialty, :bio, :image, 
                                          :email, :phone, :social_media, :is_featured, :is_active)");
                    $stmt->bindParam(':image', $image);
                    $success_message = "Especialista agregado correctamente.";
                }
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':specialty', $specialty);
                $stmt->bindParam(':bio', $bio);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':social_media', $social_media);
                $stmt->bindParam(':is_featured', $is_featured);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->execute();
                
                // Redirect to clear the form
                header('Location: specialists.php?success=' . urlencode($success_message));
                exit;
            } catch (PDOException $e) {
                $error_message = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
} 
// Edit specialist
elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM specialists WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $editing = true;
            $specialist = $result;
        }
    } catch (PDOException $e) {
        $error_message = "Error al obtener datos del especialista: " . $e->getMessage();
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
    <title>Gestionar Especialistas - Clínica Médica</title>
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
                    <h1 class="h2"><?php echo $editing ? 'Editar Especialista' : 'Gestionar Especialistas'; ?></h1>
                    <?php if ($editing): ?>
                    <a href="specialists.php" class="btn btn-secondary">
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
                        <i class="fas fa-user-md me-1"></i>
                        <?php echo $editing ? 'Editar Especialista' : 'Agregar Nuevo Especialista'; ?>
                    </div>
                    <div class="card-body">
                        <form action="specialists.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($specialist['id']); ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($specialist['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="specialty" class="form-label">Especialidad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo htmlspecialchars($specialist['specialty'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Biografía <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" required><?php echo htmlspecialchars($specialist['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($specialist['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($specialist['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Imagen</label>
                                <?php if (!empty($specialist['image']) && $specialist['image'] != 'default.jpg' && file_exists('../img/specialists/' . $specialist['image'])): ?>
                                <div class="mb-2">
                                    <img src="../img/specialists/<?php echo htmlspecialchars($specialist['image']); ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image">
                                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB.</small>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <?php 
                                    $social_media_data = isset($specialist['social_media']) ? $specialist['social_media'] : '{"facebook":"","twitter":"","instagram":""}';
                                    if (is_string($social_media_data)) {
                                        $social_media = json_decode($social_media_data, true);
                                    } else {
                                        $social_media = ['facebook' => '', 'twitter' => '', 'instagram' => ''];
                                    }
                                    if (!is_array($social_media)) {
                                        $social_media = ['facebook' => '', 'twitter' => '', 'instagram' => ''];
                                    }
                                    ?>
                                    <label for="facebook" class="form-label">Facebook</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                        <input type="text" class="form-control" id="facebook" name="facebook" value="<?php echo htmlspecialchars($social_media['facebook'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="twitter" class="form-label">Twitter</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                        <input type="text" class="form-control" id="twitter" name="twitter" value="<?php echo htmlspecialchars($social_media['twitter'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="instagram" class="form-label">Instagram</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                        <input type="text" class="form-control" id="instagram" name="instagram" value="<?php echo htmlspecialchars($social_media['instagram'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" <?php echo (isset($specialist['is_featured']) && $specialist['is_featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">Destacado en la página principal</label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo (!isset($specialist['is_active']) || $specialist['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Activo</label>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    <?php echo $editing ? 'Actualizar Especialista' : 'Agregar Especialista'; ?>
                                </button>
                                <?php if ($editing): ?>
                                <a href="specialists.php" class="btn btn-secondary">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!$editing): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-1"></i>
                        Lista de Especialistas
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Especialidad</th>
                                        <th>Destacado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($specialists)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay especialistas registrados.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($specialists as $specialist): ?>
                                    <tr>
                                        <td><?php echo $specialist['id']; ?></td>
                                        <td>
                                            <?php if (!empty($specialist['image']) && file_exists('../img/specialists/' . $specialist['image'])): ?>
                                                <img src="../img/specialists/<?php echo htmlspecialchars($specialist['image']); ?>" alt="<?php echo htmlspecialchars($specialist['name']); ?>" class="img-thumbnail" style="max-height: 50px;">
                                            <?php else: ?>
                                                <span class="text-muted">Sin imagen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($specialist['name']); ?></td>
                                        <td><?php echo htmlspecialchars($specialist['specialty']); ?></td>
                                        <td>
                                            <a href="specialists.php?feature=<?php echo $specialist['id']; ?>" class="btn btn-sm <?php echo (isset($specialist['is_featured']) && $specialist['is_featured']) ? 'btn-success' : 'btn-outline-success'; ?>">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="specialists.php?edit=<?php echo $specialist['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="specialists.php?delete=<?php echo $specialist['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este especialista?')">
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