<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Initialize variables
$id = 0;
$title = '';
$subtitle = '';
$description = '';
$icon = '';
$image = '';
$is_featured = 0;
$is_active = 1;
$page_title = 'Añadir Nuevo Servicio';
$submit_button = 'Crear Servicio';
$error_message = '';
$success_message = '';

// Check if editing existing service
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $page_title = 'Editar Servicio';
    $submit_button = 'Actualizar Servicio';
    
    try {
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($service = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = $service['title'];
            $subtitle = $service['subtitle'];
            $description = $service['description'];
            $icon = $service['icon'];
            $image = $service['image'];
            $is_featured = $service['is_featured'];
            $is_active = $service['is_active'];
        } else {
            header('Location: services.php');
            exit;
        }
    } catch(PDOException $e) {
        $error_message = "Error al cargar el servicio: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($title)) {
        $error_message = "El título es obligatorio.";
    } else {
        // Handle image upload
        $current_image = $image;
        
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../img/services/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if (!in_array($file_extension, $allowed_types)) {
                $error_message = "Solo se permiten imágenes JPG, JPEG, PNG, GIF o WEBP.";
            } elseif ($_FILES["image"]["size"] > 5000000) { // 5MB max
                $error_message = "La imagen es demasiado grande. Tamaño máximo: 5MB.";
            } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = $new_filename;
            } else {
                $error_message = "Error al subir la imagen.";
            }
        } else {
            // Keep current image if no new one uploaded
            $image = $current_image;
        }
        
        if (empty($error_message)) {
            try {
                if ($id > 0) {
                    // Update existing service
                    $stmt = $conn->prepare("UPDATE services SET 
                        title = :title, 
                        subtitle = :subtitle, 
                        description = :description, 
                        icon = :icon, 
                        image = :image, 
                        is_featured = :is_featured,
                        is_active = :is_active
                        WHERE id = :id");
                    $stmt->bindParam(':id', $id);
                    $success_message = "Servicio actualizado correctamente.";
                } else {
                    // Insert new service
                    $stmt = $conn->prepare("INSERT INTO services 
                        (title, subtitle, description, icon, image, is_featured, is_active) 
                        VALUES 
                        (:title, :subtitle, :description, :icon, :image, :is_featured, :is_active)");
                    $success_message = "Servicio creado correctamente.";
                }
                
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':subtitle', $subtitle);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':icon', $icon);
                $stmt->bindParam(':image', $image);
                $stmt->bindParam(':is_featured', $is_featured);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->execute();
                
                if ($id == 0) {
                    $id = $conn->lastInsertId();
                }
                
                // Redirect to avoid form resubmission
                header("Location: add_service.php?id=$id&success=1");
                exit;
            } catch(PDOException $e) {
                $error_message = "Error al guardar el servicio: " . $e->getMessage();
            }
        }
    }
}

// Display success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Servicio guardado correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Clínica Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#description',
            height: 300,
            plugins: 'lists link image',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
        });
    </script>
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
                                    <h2><?php echo $page_title; ?></h2>
                                    <p>Gestiona la información de los servicios que ofrece la clínica</p>
                                </div>
                                <a href="services.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver a Servicios
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Service Form -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="subtitle" class="form-label">Subtítulo</label>
                                                    <input type="text" class="form-control" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($subtitle); ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Descripción <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="icon" class="form-label">Icono (Clase Font Awesome)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                                        <input type="text" class="form-control" id="icon" name="icon" value="<?php echo htmlspecialchars($icon); ?>" placeholder="Ejemplo: fas fa-stethoscope">
                                                    </div>
                                                    <div class="form-text">
                                                        <a href="https://fontawesome.com/icons" target="_blank">Buscar íconos disponibles</a>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="image" class="form-label">Imagen</label>
                                                    <input type="file" class="form-control" id="image" name="image">
                                                    <?php if (!empty($image)): ?>
                                                        <div class="mt-2">
                                                            <label>Imagen actual:</label>
                                                            <div class="mt-2 position-relative">
                                                                <img src="../img/services/<?php echo htmlspecialchars($image); ?>" class="img-thumbnail" style="max-height: 150px;">
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="mb-3 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo $is_featured ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_featured">Destacado</label>
                                                </div>
                                                
                                                <div class="mb-3 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo $is_active ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_active">Activo</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i><?php echo $submit_button; ?>
                                            </button>
                                            <a href="services.php" class="btn btn-secondary ms-2">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>