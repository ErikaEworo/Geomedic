<div class="top-nav">
    <div class="top-nav-left">
        <div class="page-title">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            switch ($current_page) {
                case 'index.php':
                    echo 'Dashboard';
                    break;
                case 'messages.php':
                    echo 'Mensajes';
                    break;
                case 'services.php':
                    echo 'Servicios';
                    break;
                case 'specialists.php':
                    echo 'Especialistas';
                    break;
                case 'news.php':
                    echo 'Noticias';
                    break;
                case 'testimonials.php':
                    echo 'Testimonios';
                    break;
                case 'settings.php':
                    echo 'Configuración';
                    break;
                default:
                    echo 'Admin Panel';
            }
            ?>
        </div>
    </div>
    <div class="top-nav-right">
        <div class="admin-profile">
            <span class="admin-name"><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
            <div class="admin-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
        </div>
    </div>
</div>