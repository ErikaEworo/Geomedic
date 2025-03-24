// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Check if we're on mobile or desktop
            if (window.innerWidth > 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.style.width = '250px';
                    mainContent.style.marginLeft = '250px';
                    
                    // Show text in sidebar
                    document.querySelectorAll('.sidebar-menu li a span, .sidebar-header h3').forEach(function(el) {
                        el.style.display = 'block';
                    });
                } else {
                    sidebar.style.width = '70px';
                    mainContent.style.marginLeft = '70px';
                    
                    // Hide text in sidebar
                    document.querySelectorAll('.sidebar-menu li a span, .sidebar-header h3').forEach(function(el) {
                        el.style.display = 'none';
                    });
                }
            }
        });
    }
    
    // Responsive adjustments
    function handleResize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
            mainContent.style.marginLeft = '0';
            
            // Show text in sidebar for mobile (will be hidden by CSS when sidebar is closed)
            document.querySelectorAll('.sidebar-menu li a span, .sidebar-header h3').forEach(function(el) {
                el.style.display = '';
            });
        } else if (window.innerWidth <= 992) {
            sidebar.style.width = '70px';
            mainContent.style.marginLeft = '70px';
            
            // Hide text in sidebar
            document.querySelectorAll('.sidebar-menu li a span, .sidebar-header h3').forEach(function(el) {
                el.style.display = 'none';
            });
        } else {
            sidebar.style.width = '250px';
            mainContent.style.marginLeft = '250px';
            
            // Show text in sidebar
            document.querySelectorAll('.sidebar-menu li a span, .sidebar-header h3').forEach(function(el) {
                el.style.display = '';
            });
        }
    }
    
    // Initial call and event listener
    handleResize();
    window.addEventListener('resize', handleResize);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});