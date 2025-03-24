<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once 'config.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => 'Ha ocurrido un error al procesar la solicitud.'
);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        $response['message'] = "Error de conexión: " . $conn->connect_error;
        echo json_encode($response);
        exit;
    }
    
    // Validate and sanitize inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($subject) || empty($message)) {
        $response['message'] = "Por favor, complete todos los campos obligatorios.";
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Por favor, ingrese un correo electrónico válido.";
        echo json_encode($response);
        exit;
    }
    
    // Create contacts table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS `contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(50) NOT NULL,
        `subject` varchar(100) NOT NULL,
        `message` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `status` varchar(20) DEFAULT 'nuevo',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if (!$conn->query($createTable)) {
        $response['message'] = "Error al crear la tabla: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Insert contact into database
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    
    if ($stmt->execute()) {
        // Optionally, send an email notification
        $to = "admin@yourwebsite.com"; // Replace with your email
        $email_subject = "Nuevo mensaje de contacto: " . $subject;
        $email_body = "Has recibido un nuevo mensaje de contacto.\n\n" .
                      "Nombre: $name\n" .
                      "Email: $email\n" .
                      "Teléfono: $phone\n" .
                      "Asunto: $subject\n" .
                      "Mensaje: $message\n";
        $headers = "From: $email";
        
        // Uncomment this line to actually send the email
        // mail($to, $email_subject, $email_body, $headers);
        
        $response['success'] = true;
        $response['message'] = "¡Gracias por contactarnos! Tu mensaje ha sido enviado correctamente.";
    } else {
        $response['message'] = "Error al guardar el mensaje: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
