<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: messages.php');
    exit;
}

$id = (int)$_GET['id'];

// Process message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message']) && isset($_POST['message_id'])) {
    $delete_id = (int)$_POST['message_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->bindParam(':id', $delete_id);
        $stmt->execute();
        
        // Redirect to messages list after deletion
        header('Location: messages.php?deleted=1');
        exit;
    } catch(PDOException $e) {
        $error_message = "Error al eliminar el mensaje: " . $e->getMessage();
    }
}

// Get message details
try {
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        header('Location: messages.php');
        exit;
    }
    
    // Mark as read if not already
    if (!$message['is_read']) {
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $message['is_read'] = 1;
    }
} catch(PDOException $e) {
    $error_message = "Error al obtener el mensaje: " . $e->getMessage();
}

// Handle reply form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $reply = trim($_POST['reply']);
    $to = $message['email'];
    $subject = "RE: " . $message['subject'];
    
    // Email headers
    $headers = "From: info@clinicamedica.com\r\n";
    $headers .= "Reply-To: info@clinicamedica.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email body
    $email_body = "<html><body>";
    $email_body .= "<p>Estimado/a " . htmlspecialchars($message['name']) . ",</p>";
    $email_body .= "<p>" . nl2br(htmlspecialchars($reply)) . "</p>";
    $email_body .= "<p>Atentamente,<br>Equipo de Clínica Médica</p>";
    $email_body .= "<hr>";
    $email_body .= "<p><strong>Mensaje original:</strong></p>";
    $email_body .= "<p>" . nl2br(htmlspecialchars($message['message'])) . "</p>";
    $email_body .= "</body></html>";
    
    // Send email
    if (mail($to, $subject, $email_body, $headers)) {
        $success_message = "Respuesta enviada correctamente a " . htmlspecialchars($message['email']);
    } else {
        $error_message = "Error al enviar la respuesta. Por favor, inténtelo de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Mensaje - Clínica Médica</title>
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
                                <h2>Ver Mensaje</h2>
                                <a href="messages.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver a Mensajes
                                </a>
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

                    <!-- Message Details -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><?php echo htmlspecialchars($message['subject']); ?></h4>
                                    <span class="badge <?php echo $message['is_read'] ? 'bg-success' : 'bg-primary'; ?>">
                                        <?php echo $message['is_read'] ? 'Leído' : 'Nuevo'; ?>
                                    </span>
                                </div>
                                <div class="content-card-body">
                                    <div class="message-info mb-4">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>De:</strong> <?php echo htmlspecialchars($message['name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></p>
                                                <p>
                                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-reply me-1"></i>Responder por Email
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteMessageModal">
                                                        <i class="fas fa-trash me-1"></i>Eliminar
                                                    </button>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="message-content p-4 bg-light rounded mb-4">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                    
                                    <!-- Reply Form -->
                                    <div class="reply-form">
                                        <h5>Responder a este mensaje</h5>
                                        <form method="POST">
                                            <div class="mb-3">
                                                <textarea class="form-control" name="reply" rows="5" placeholder="Escribe tu respuesta aquí..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                                            </button>
                                        </form>
                                    </div>
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
                    <p>¿Estás seguro de eliminar el mensaje de <strong><?php echo htmlspecialchars($message['name']); ?></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" name="delete_message" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-script.js"></script>
</body>
</html>