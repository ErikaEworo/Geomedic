<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Include sidebar (topnav is usually included in the sidebar)
include_once 'includes/sidebar.php';
include_once 'includes/topnav.php';

// Handle section update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    $section_id = (int)$_POST['section_id'];
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle image upload if present
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $tmp = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_dir = '../img/sections/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($tmp, $upload_dir . $new_filename)) {
                $image = $new_filename;
            }
        }
    } else {
        // Keep existing image if no new one
        $stmt = $conn->prepare("SELECT image FROM homepage_sections WHERE id = :id");
        $stmt->bindParam(':id', $section_id);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $image = $row['image'];
        }
    }
    
    // Update the section
    $stmt = $conn->prepare("UPDATE homepage_sections SET title = :title, subtitle = :subtitle, content = :content, image = :image, is_active = :is_active WHERE id = :id");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':subtitle', $subtitle);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':is_active', $is_active);
    $stmt->bindParam(':id', $section_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Section updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating section: ' . $conn->errorInfo()[2] . '</div>';
    }
}

// Get section to edit if ID is provided
$current_section = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $section_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM homepage_sections WHERE id = :id");
    $stmt->bindParam(':id', $section_id);
    $stmt->execute();
    $current_section = $stmt->fetch();
}

// Toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_active' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE homepage_sections SET is_active = 1 - is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: homepage_sections.php");
    exit;
}

// Get all sections
$stmt = $conn->prepare("SELECT * FROM homepage_sections ORDER BY id");
$stmt->execute();
$sections = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Homepage Sections</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Homepage Sections</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <?php if (isset($message)) echo $message; ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $current_section ? 'Edit Section: ' . htmlspecialchars($current_section['section_name']) : 'Select a Section to Edit'; ?></h3>
                        </div>
                        <?php if ($current_section): ?>
                        <form method="post" enctype="multipart/form-data">
                            <div class="card-body">
                                <input type="hidden" name="section_id" value="<?php echo $current_section['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($current_section['title']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subtitle">Subtitle</label>
                                    <textarea class="form-control" id="subtitle" name="subtitle" rows="2"><?php echo htmlspecialchars($current_section['subtitle']); ?></textarea>
                                </div>
                                
                                <?php if (in_array($current_section['section_name'], ['about', 'hero', 'contact'])): ?>
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="4"><?php echo htmlspecialchars($current_section['content']); ?></textarea>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (in_array($current_section['section_name'], ['hero', 'about', 'contact'])): ?>
                                <div class="form-group">
                                    <label for="image">Image</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="image" name="image">
                                            <label class="custom-file-label" for="image">Choose file</label>
                                        </div>
                                    </div>
                                    <?php if ($current_section['image']): ?>
                                    <div class="mt-2">
                                        <img src="../img/sections/<?php echo $current_section['image']; ?>" alt="Current Image" style="max-width: 100px">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $current_section['is_active'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" name="update_section" class="btn btn-primary">Update Section</button>
                                <a href="homepage_sections.php" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="card-body">
                            <p class="text-muted">Select a section from the list to edit its content.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Homepage Sections</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 10px">#</th>
                                        <th>Section</th>
                                        <th>Title</th>
                                        <th style="width: 100px">Status</th>
                                        <th style="width: 120px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $section): ?>
                                    <tr>
                                        <td><?php echo $section['id']; ?></td>
                                        <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                                        <td><?php echo htmlspecialchars($section['title']); ?></td>
                                        <td>
                                            <a href="homepage_sections.php?action=toggle_active&id=<?php echo $section['id']; ?>" class="btn btn-sm <?php echo $section['is_active'] ? 'btn-success' : 'btn-danger'; ?>">
                                                <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="homepage_sections.php?edit=<?php echo $section['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize editor for content if needed
$(document).ready(function() {
    if ($('#content').length) {
        $('#content').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']]
            ]
        });
    }
    
    // Show filename on file select
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>

</body>
</html>