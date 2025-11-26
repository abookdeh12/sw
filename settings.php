<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('settings')) {
    die('Access denied');
}

// Get all staff accounts
$stmt = $pdo->query("SELECT u.*, sp.* FROM users u 
                     LEFT JOIN staff_permissions sp ON u.id = sp.user_id 
                     WHERE u.role = 'staff'");
$staff_accounts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .settings-sections {
            display: grid;
            gap: 20px;
        }
        
        .settings-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .color-picker {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .color-option {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
        }
        
        .color-option.selected {
            border-color: #333;
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
        }
        
        .permission-item input {
            width: auto;
            margin-right: 8px;
        }
        
        .staff-table {
            margin-top: 20px;
        }
        
        .staff-table td {
            vertical-align: middle;
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Settings</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="settings-sections">
            <!-- System Colors -->
            <div class="settings-card">
                <h2>System Theme</h2>
                <p>Choose your preferred color scheme</p>
                <div class="color-picker">
                    <div class="color-option selected" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" onclick="changeTheme('purple')"></div>
                    <div class="color-option" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" onclick="changeTheme('pink')"></div>
                    <div class="color-option" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" onclick="changeTheme('blue')"></div>
                    <div class="color-option" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);" onclick="changeTheme('green')"></div>
                    <div class="color-option" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);" onclick="changeTheme('sunset')"></div>
                </div>
            </div>
            
            <!-- Staff Account Management -->
            <div class="settings-card">
                <h2>Staff Account Management</h2>
                <button class="btn-primary" onclick="showCreateStaffModal()">Create Staff Account</button>
                
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_accounts as $staff): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['username']); ?></td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td>
                                <?php
                                $permissions = [];
                                if ($staff['dashboard_access']) $permissions[] = 'Dashboard';
                                if ($staff['clients_access']) $permissions[] = 'Clients';
                                if ($staff['gym_access']) $permissions[] = 'Gym';
                                if ($staff['pos_access']) $permissions[] = 'POS';
                                if ($staff['reports_access']) $permissions[] = 'Reports';
                                if ($staff['packages_items_access']) $permissions[] = 'Packages';
                                if ($staff['social_media_access']) $permissions[] = 'Social';
                                if ($staff['settings_access']) $permissions[] = 'Settings';
                                echo implode(', ', $permissions);
                                ?>
                            </td>
                            <td>
                                <button onclick="editStaff(<?php echo $staff['id']; ?>)" class="btn-secondary" style="padding: 5px 10px; font-size: 12px;">Edit</button>
                                <button onclick="deleteStaff(<?php echo $staff['id']; ?>)" class="btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Change Password -->
            <div class="settings-card">
                <h2>Change Password</h2>
                <form onsubmit="changePassword(event)">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="newPassword" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPassword" required>
                    </div>
                    <button type="submit" class="btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Create Staff Modal -->
    <div id="createStaffModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateStaffModal()">&times;</span>
            <h2>Create Staff Account</h2>
            <form onsubmit="createStaff(event)">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="staffUsername" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="staffPassword" required>
                </div>
                
                <div class="form-group">
                    <label>Page Access</label>
                    <div class="permissions-grid">
                        <div class="permission-item">
                            <input type="checkbox" id="perm_dashboard" name="permissions" value="dashboard">
                            <label for="perm_dashboard">Dashboard</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_clients" name="permissions" value="clients">
                            <label for="perm_clients">Clients</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_gym" name="permissions" value="gym">
                            <label for="perm_gym">Gym</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_pos" name="permissions" value="pos">
                            <label for="perm_pos">POS</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_reports" name="permissions" value="reports">
                            <label for="perm_reports">Reports</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_packages" name="permissions" value="packages_items">
                            <label for="perm_packages">Packages & Items</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_social" name="permissions" value="social_media">
                            <label for="perm_social">Social Media</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_settings" name="permissions" value="settings">
                            <label for="perm_settings">Settings</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
        </div>
    </div>
    
    <script>
        function showCreateStaffModal() {
            document.getElementById('createStaffModal').style.display = 'block';
        }
        
        function closeCreateStaffModal() {
            document.getElementById('createStaffModal').style.display = 'none';
        }
        
        function createStaff(event) {
            event.preventDefault();
            
            const username = document.getElementById('staffUsername').value;
            const password = document.getElementById('staffPassword').value;
            const permissions = {};
            
            document.querySelectorAll('input[name="permissions"]:checked').forEach(checkbox => {
                permissions[checkbox.value + '_access'] = true;
            });
            
            fetch('actions/create_staff.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    username: username,
                    password: password,
                    permissions: permissions
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Staff account created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function changePassword(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            fetch('actions/change_password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function changeTheme(theme) {
            // Store theme preference
            localStorage.setItem('gymTheme', theme);
            
            // Apply theme visually
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // Apply theme to sidebar immediately
            applyTheme(theme);
            
            // Save to server
            fetch('actions/save_theme.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({theme: theme})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Theme saved
                }
            });
        }
        
        function applyTheme(theme) {
            const themes = {
                'purple': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'pink': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'blue': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'green': 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'sunset': 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
            };
            
            const sidebar = document.querySelector('.sidebar');
            if (sidebar && themes[theme]) {
                sidebar.style.background = themes[theme];
            }
        }
        
        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('gymTheme') || 'purple';
            applyTheme(savedTheme);
            
            // Mark the correct color option as selected
            const themeMap = {
                'purple': 0, 'pink': 1, 'blue': 2, 'green': 3, 'sunset': 4
            };
            const options = document.querySelectorAll('.color-option');
            options.forEach(opt => opt.classList.remove('selected'));
            if (options[themeMap[savedTheme]]) {
                options[themeMap[savedTheme]].classList.add('selected');
            }
        });
        
        function deleteStaff(id) {
            if (confirm('Are you sure you want to delete this staff account?')) {
                fetch('actions/delete_staff.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('createStaffModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
