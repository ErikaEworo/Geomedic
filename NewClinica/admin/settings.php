<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

// Get all settings
try {
    $stmt = $conn->query("SELECT * FROM settings ORDER BY setting_key ASC");
    $settings = $stmt->fetchAll();
    
    // Convert to associative array for easier access
    $settings_array = [];
    foreach ($settings as $setting) {
        $settings_array[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    $error_message = "Error al obtener la configuración: " . $e->getMessage();
    $settings = [];
    $settings_array = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix
                $value = trim($value);
                
                $stmt = $conn->prepare("UPDATE settings SET setting_value = :value, updated_at = CURRENT_TIMESTAMP WHERE setting_key = :key");
                $stmt->bindParam(':value', $value);
                $stmt->bindParam(':key', $setting_key);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Configuración actualizada correctamente.";
        
        // Refresh settings
        $stmt = $conn->query("SELECT * FROM settings ORDER BY setting_key ASC");
        $settings = $stmt->fetchAll();
        
        // Update associative array
        $settings_array = [];
        foreach ($settings as $setting) {
            $settings_array[$setting['setting_key']] = $setting['setting_value'];
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = "Error al actualizar la configuración: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Clínica Médica</title>
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
                    <h1 class="h2">Configuración del Sitio</h1>
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
                
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="settings-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                    <i class="fas fa-cog me-1"></i> General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">
                                    <i class="fas fa-phone me-1"></i> Contacto
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">
                                    <i class="fas fa-share-alt me-1"></i> Redes Sociales
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <form action="settings.php" method="POST">
                            <div class="tab-content" id="settings-content">
                                <!-- General Settings Tab -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="site_name" class="form-label">Nombre del Sitio</label>
                                            <input type="text" class="form-control" id="site_name" name="setting_site_name" value="<?php echo htmlspecialchars($settings_array['site_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="site_description" class="form-label">Descripción del Sitio</label>
                                            <input type="text" class="form-control" id="site_description" name="setting_site_description" value="<?php echo htmlspecialchars($settings_array['site_description'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="footer_text" class="form-label">Texto del Pie de Página</label>
                                        <textarea class="form-control" id="footer_text" name="setting_footer_text" rows="3"><?php echo htmlspecialchars($settings_array['footer_text'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Contact Settings Tab -->
                                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="contact_email" class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control" id="contact_email" name="setting_contact_email" value="<?php echo htmlspecialchars($settings_array['contact_email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="contact_phone" class="form-label">Teléfono</label>
                                            <input type="text" class="form-control" id="contact_phone" name="setting_contact_phone" value="<?php echo htmlspecialchars($settings_array['contact_phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contact_address" class="form-label">Dirección</label>
                                        <textarea class="form-control" id="contact_address" name="setting_contact_address" rows="3"><?php echo htmlspecialchars($settings_array['contact_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="contact_hours" class="form-label">Horario de Atención</label>
                                            <textarea class="form-control" id="contact_hours" name="setting_contact_hours" rows="3"><?php echo htmlspecialchars($settings_array['contact_hours'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="google_maps" class="form-label">URL de Google Maps</label>
                                            <input type="text" class="form-control" id="google_maps" name="setting_google_maps" value="<?php echo htmlspecialchars($settings_array['google_maps'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Social Media Settings Tab -->
                                <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="social_facebook" class="form-label">Facebook</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                                <input type="text" class="form-control" id="social_facebook" name="setting_social_facebook" value="<?php echo htmlspecialchars($settings_array['social_facebook'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="social_twitter" class="form-label">Twitter</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                                <input type="text" class="form-control" id="social_twitter" name="setting_social_twitter" value="<?php echo htmlspecialchars($settings_array['social_twitter'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="social_instagram" class="form-label">Instagram</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                                <input type="text" class="form-control" id="social_instagram" name="setting_social_instagram" value="<?php echo htmlspecialchars($settings_array['social_instagram'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="social_youtube" class="form-label">YouTube</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                                <input type="text" class="form-control" id="social_youtube" name="setting_social_youtube" value="<?php echo htmlspecialchars($settings_array['social_youtube'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="social_linkedin" class="form-label">LinkedIn</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                                <input type="text" class="form-control" id="social_linkedin" name="setting_social_linkedin" value="<?php echo htmlspecialchars($settings_array['social_linkedin'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="social_whatsapp" class="form-label">WhatsApp</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                                <input type="text" class="form-control" id="social_whatsapp" name="setting_social_whatsapp" value="<?php echo htmlspecialchars($settings_array['social_whatsapp'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Configuración
                                </button>
                            </div>
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