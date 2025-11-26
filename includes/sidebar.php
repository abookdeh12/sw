<div class="sidebar" id="mainSidebar">
<script>
// Apply saved theme immediately
(function() {
    const theme = localStorage.getItem('gymTheme') || 'purple';
    const themes = {
        'purple': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'pink': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'blue': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'green': 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'sunset': 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    };
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mainSidebar');
        if (sidebar && themes[theme]) {
            sidebar.style.background = themes[theme];
        }
    });
})();
</script>
    <div class="logo">
        <h2>Gym System</h2>
    </div>
    
    <nav class="nav-menu">
        <ul>
            <?php if (hasAccess('dashboard')): ?>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('clients')): ?>
            <li>
                <a href="clients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>">
                    <span class="icon">👥</span>
                    <span>Clients</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('gym')): ?>
            <li>
                <a href="gym.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'gym.php' ? 'active' : ''; ?>">
                    <span class="icon">🏋️</span>
                    <span>Gym</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('pos')): ?>
            <li>
                <a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                    <span class="icon">💳</span>
                    <span>POS</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('reports')): ?>
            <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <span class="icon">📈</span>
                    <span>Reports</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('packages_items')): ?>
            <li>
                <a href="packages_items.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'packages_items.php' ? 'active' : ''; ?>">
                    <span class="icon">📦</span>
                    <span>Packages & Items</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('social_media')): ?>
            <li>
                <a href="social_media.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'social_media.php' ? 'active' : ''; ?>">
                    <span class="icon">💬</span>
                    <span>Social Media</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAccess('settings')): ?>
            <li>
                <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <span class="icon">⚙️</span>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
