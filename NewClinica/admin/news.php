<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database configuration and common functions
require_once 'config.php';
require_once 'includes/db_connect.php';

// Include sidebar and topnav
include_once 'includes/sidebar.php';
include_once 'includes/topnav.php';

// Initialize variables
$news = [
    'id' => '',
    'title' => '',
    'content' => '',
    'summary' => '',
    'author' => '',
    'image' => '',
    'is_published' => 1
];
$editing = false;
$news_articles = [];

// Handle image upload function
function handleImageUpload($file) {
    $target_dir = "../img/news/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($file["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $new_file_name;
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'El archivo no es una imagen.'];
    }
    
    // Check file size (limit to 2MB)
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 2MB.'];
    }
    
    // Allow certain file formats
    if ($file_ext != "jpg" && $file_ext != "png" && $file_ext != "jpeg" && $file_ext != "webp") {
        return ['success' => false, 'message' => 'Solo se permiten archivos JPG, JPEG, PNG y WEBP.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_name' => $new_file_name];
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo.'];
    }
}

// Handle form submission for create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = $_POST['title'];
    $content = $_POST['content'];
    $summary = isset($_POST['summary']) ? $_POST['summary'] : substr(strip_tags($content), 0, 200) . '...';
    $author = isset($_POST['author']) ? $_POST['author'] : '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // Handle image upload
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $result = handleImageUpload($_FILES['image']);
        if ($result['success']) {
            $image_name = $result['file_name'];
        } else {
            $error_message = $result['message'];
        }
    }
    
    if (!isset($error_message)) {
        try {
            if ($id > 0) {
                // Update existing news
                $sql = "UPDATE news SET 
                        title = :title, 
                        content = :content, 
                        summary = :summary, 
                        author = :author, 
                        is_published = :is_published";
                
                $params = [
                    ':title' => $title, 
                    ':content' => $content, 
                    ':summary' => $summary, 
                    ':author' => $author, 
                    ':is_published' => $is_published
                ];
                
                if ($image_name) {
                    $sql .= ", image = :image";
                    $params[':image'] = $image_name;
                    
                    // Get the old image to delete it
                    $old_image_query = $conn->prepare("SELECT image FROM news WHERE id = :id");
                    $old_image_query->bindParam(':id', $id);
                    $old_image_query->execute();
                    $old_image_row = $old_image_query->fetch();
                    
                    if ($old_image_row && !empty($old_image_row['image']) && file_exists('../img/news/' . $old_image_row['image'])) {
                        unlink('../img/news/' . $old_image_row['image']);
                    }
                }
                
                $sql .= " WHERE id = :id";
                $params[':id'] = $id;
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = 'Noticia actualizada correctamente.';
                } else {
                    $error_message = 'No se realizaron cambios en la noticia.';
                }
            } else {
                // Create new news
                $sql = "INSERT INTO news (title, content, summary, author, image, is_published, published_at) 
                        VALUES (:title, :content, :summary, :author, :image, :is_published, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':summary', $summary);
                $stmt->bindParam(':author', $author);
                $stmt->bindParam(':image', $image_name);
                $stmt->bindParam(':is_published', $is_published);
                
                if ($stmt->execute()) {
                    $success_message = 'Noticia creada correctamente.';
                } else {
                    $error_message = 'Error al crear la noticia.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

// Handle publish/unpublish action
if (isset($_GET['publish'])) {
    $id = (int)$_GET['publish'];
    
    try {
        // Get current publish status
        $status_query = $conn->prepare("SELECT is_published FROM news WHERE id = :id");
        $status_query->bindParam(':id', $id);
        $status_query->execute();
        $status_row = $status_query->fetch();
        
        if ($status_row) {
            $new_status = $status_row['is_published'] ? 0 : 1;
            
            // Update publish status
            $update_stmt = $conn->prepare("UPDATE news SET is_published = :status WHERE id = :id");
            $update_stmt->bindParam(':status', $new_status);
            $update_stmt->bindParam(':id', $id);
            
            if ($update_stmt->execute()) {
                $success_message = 'Estado de publicación actualizado correctamente.';
            } else {
                $error_message = 'Error al actualizar el estado de publicación.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Error en la base de datos: ' . $e->getMessage();
    }
}

// Handle edit action
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch();
    
    if ($row) {
        $news = $row;
        $editing = true;
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Get the image to delete it
        $image_query = $conn->prepare("SELECT image FROM news WHERE id = :id");
        $image_query->bindParam(':id', $id);
        $image_query->execute();
        $image_row = $image_query->fetch();
        
        if ($image_row && !empty($image_row['image']) && file_exists('../img/news/' . $image_row['image'])) {
            unlink('../img/news/' . $image_row['image']);
        }
        
        $stmt = $conn->prepare("DELETE FROM news WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success_message = 'Noticia eliminada correctamente.';
        } else {
            $error_message = 'Error al eliminar la noticia.';
        }
    } catch (PDOException $e) {
        $error_message = 'Error en la base de datos: ' . $e->getMessage();
    }
}

// Fetch all news for listing
try {
    $result = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
    $news_articles = $result->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Error al obtener las noticias: ' . $e->getMessage();
    $news_articles = [];
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $editing ? 'Editar Noticia' : 'Gestionar Noticias'; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Noticias</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
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
                    <i class="fas fa-newspaper me-1"></i>
                    <?php echo $editing ? 'Editar Noticia' : 'Agregar Nueva Noticia'; ?>
                </div>
                <div class="card-body">
                    <form action="news.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($news['id']); ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($news['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="summary" class="form-label">Resumen</label>
                            <textarea class="form-control" id="summary" name="summary" rows="3"><?php echo htmlspecialchars($news['summary'] ?? ''); ?></textarea>
                            <small class="text-muted">Un breve resumen para mostrar en la vista previa (opcional).</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenido <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="8" required><?php echo htmlspecialchars($news['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="author" class="form-label">Autor</label>
                                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($news['author'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label">Imagen</label>
                                <?php if (!empty($news['image']) && file_exists('../img/news/' . $news['image'])): ?>
                                <div class="mb-2">
                                    <img src="../img/news/<?php echo htmlspecialchars($news['image']); ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image">
                                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamaño máximo: 2MB.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" <?php echo ($news['is_published'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Publicar</label>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $editing ? 'Actualizar Noticia' : 'Agregar Noticia'; ?>
                            </button>
                            <?php if ($editing): ?>
                            <a href="news.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!$editing): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    Lista de Noticias
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($news_articles)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay noticias registradas.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($news_articles as $article): ?>
                                <tr>
                                    <td><?php echo $article['id']; ?></td>
                                    <td>
                                        <?php if (!empty($article['image']) && file_exists('../img/news/' . $article['image'])): ?>
                                            <img src="../img/news/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="img-thumbnail" style="max-height: 50px;">
                                        <?php else: ?>
                                            <span class="text-muted">Sin imagen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                                    <td><?php echo htmlspecialchars($article['author'] ?? 'No especificado'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                                    <td>
                                        <a href="news.php?publish=<?php echo $article['id']; ?>" class="btn btn-sm <?php echo $article['is_published'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $article['is_published'] ? 'Publicado' : 'Borrador'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="news.php?edit=<?php echo $article['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="news.php?delete=<?php echo $article['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar esta noticia?')">
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
        </div>
    </div>
</div>

<script>
    // Initialize editor for content if needed
    $(document).ready(function() {
        if ($('#content').length) {
            $('#content').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']]
                ]
            });
        }
    });
</script>