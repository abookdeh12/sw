<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('packages_items')) {
    die('Access denied');
}

// Get all packages
$stmt = $pdo->query("SELECT * FROM packages ORDER BY id DESC");
$packages = $stmt->fetchAll();

// Get all items
$stmt = $pdo->query("SELECT * FROM items ORDER BY id DESC");
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages & Items - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .management-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .add-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .items-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .item-row, .package-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .item-row:hover, .package-row:hover {
            background: #f8f9fa;
        }
        
        .update-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .management-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Packages & Items Management</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="management-grid">
            <!-- Items Management -->
            <div class="management-section">
                <div class="section-header">
                    <h2>Items</h2>
                </div>
                
                <div class="add-form">
                    <h4>Create New Item</h4>
                    <form onsubmit="addItem(event)">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" id="itemName" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Price (LBP)</label>
                                <input type="number" id="itemPrice" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label>Stock</label>
                                <input type="number" id="itemStock" min="0" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Add Item</button>
                    </form>
                </div>
                
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                            <small>L.L. <?php echo number_format($item['price'], 0); ?> | Stock: <?php echo $item['stock']; ?></small>
                        </div>
                        <div>
                            <button onclick="editItem(<?php echo $item['id']; ?>)" class="btn-secondary" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Packages Management -->
            <div class="management-section">
                <div class="section-header">
                    <h2>Packages</h2>
                </div>
                
                <div class="add-form">
                    <h4>Create New Package</h4>
                    <form onsubmit="addPackage(event)">
                        <div class="form-group">
                            <label>Package Name</label>
                            <input type="text" id="packageName" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Price</label>
                                <input type="number" id="packagePrice" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <select id="packageCurrency" required>
                                    <option value="USD">USD</option>
                                    <option value="LBP">LBP</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Duration (days)</label>
                            <input type="number" id="packageDuration" min="1" required>
                        </div>
                        <button type="submit" class="btn-primary">Add Package</button>
                    </form>
                </div>
                
                <div class="items-list">
                    <?php foreach ($packages as $package): ?>
                    <div class="package-row">
                        <div>
                            <strong><?php echo htmlspecialchars($package['name']); ?></strong><br>
                            <small>
                                <?php echo $package['currency'] == 'USD' ? '$' : 'L.L. '; ?>
                                <?php echo number_format($package['price'], $package['currency'] == 'USD' ? 2 : 0); ?>
                                | <?php echo $package['duration']; ?> days
                            </small>
                        </div>
                        <div>
                            <button onclick="editPackage(<?php echo $package['id']; ?>)" class="btn-secondary" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Update Client Membership -->
        <div class="update-section">
            <h2>Update Client Membership</h2>
            <form onsubmit="updateMembership(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Search Client</label>
                        <input type="text" id="clientSearch" placeholder="Name, family name, or phone..." required>
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select id="updateAction" onchange="toggleUpdateFields()">
                            <option value="extend">Extend Membership</option>
                            <option value="change_date">Change End Date</option>
                        </select>
                    </div>
                </div>
                
                <div id="extendFields" class="form-row">
                    <div class="form-group">
                        <label>Additional Days</label>
                        <input type="number" id="additionalDays" min="1">
                    </div>
                </div>
                
                <div id="changeDateFields" class="form-row" style="display: none;">
                    <div class="form-group">
                        <label>New End Date</label>
                        <input type="date" id="newEndDate">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Update Membership</button>
            </form>
        </div>
    </div>
    
    <script>
        function addItem(event) {
            event.preventDefault();
            
            const data = {
                name: document.getElementById('itemName').value,
                price: document.getElementById('itemPrice').value,
                stock: document.getElementById('itemStock').value
            };
            
            fetch('actions/add_item.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Item added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }
        
        function addPackage(event) {
            event.preventDefault();
            
            const data = {
                name: document.getElementById('packageName').value,
                price: document.getElementById('packagePrice').value,
                currency: document.getElementById('packageCurrency').value,
                duration: document.getElementById('packageDuration').value
            };
            
            fetch('actions/add_package.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Package added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }
        
        function toggleUpdateFields() {
            const action = document.getElementById('updateAction').value;
            document.getElementById('extendFields').style.display = action === 'extend' ? 'grid' : 'none';
            document.getElementById('changeDateFields').style.display = action === 'change_date' ? 'grid' : 'none';
        }
        
        function updateMembership(event) {
            event.preventDefault();
            
            const search = document.getElementById('clientSearch').value;
            const action = document.getElementById('updateAction').value;
            
            let data = {
                search: search,
                action: action
            };
            
            if (action === 'extend') {
                data.days = document.getElementById('additionalDays').value;
            } else {
                data.end_date = document.getElementById('newEndDate').value;
            }
            
            fetch('actions/update_membership.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Membership updated successfully!');
                    document.getElementById('clientSearch').value = '';
                    document.getElementById('additionalDays').value = '';
                    document.getElementById('newEndDate').value = '';
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }
    </script>
</body>
</html>
