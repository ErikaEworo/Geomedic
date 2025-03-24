<div class="sidebar">
    <div class="sidebar-header">
        <h3>Clínica Médica</h3>
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
    <div class="sidebar-menu">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                <a href="messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Mensajes</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
                <a href="services.php">
                    <i class="fas fa-stethoscope"></i>
                    <span>Servicios</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'specialists.php' ? 'active' : ''; ?>">
                <a href="specialists.php">
                    <i class="fas fa-user-md"></i>
                    <span>Especialistas</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : ''; ?>">
                <a href="news.php">
                    <i class="fas fa-newspaper"></i>
                    <span>Noticias</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'active' : ''; ?>">
                <a href="testimonials.php">
                    <i class="fas fa-comment"></i>
                    <span>Testimonios</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'signup.php' ? 'active' : ''; ?>">
                <a href="signup.php">
                    <i class="fas fa-user-plus"></i>
                    <span>Crear Administrador</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'homepage_sections.php' ? 'active' : ''; ?>">
                <a href="homepage_sections.php">
                    <i class="fas fa-home"></i>
                    <span>Secciones de la Página de Inicio</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </div>
</div>