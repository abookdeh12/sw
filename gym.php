<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('gym')) {
    die('Access denied');
}

// Get all packages for assignment
$stmt = $pdo->query("SELECT * FROM packages WHERE active = 1");
$packages = $stmt->fetchAll();

// Get today's attendance
$stmt = $pdo->prepare("
    SELECT a.*, c.name as client_name, c.phone, p.name as package_name,
           cp.start_date, cp.end_date, cp.status
    FROM attendance a
    JOIN clients c ON a.client_id = c.id
    JOIN packages p ON a.package_id = p.id
    JOIN client_packages cp ON cp.client_id = c.id AND cp.package_id = p.id
    WHERE DATE(a.check_in_time) = CURDATE()
    ORDER BY a.check_in_time DESC
");
$stmt->execute();
$attendance_today = $stmt->fetchAll();

// Search for specific date attendance
$search_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if ($search_date != date('Y-m-d')) {
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as client_name, c.phone, p.name as package_name,
               cp.start_date, cp.end_date, cp.status
        FROM attendance a
        JOIN clients c ON a.client_id = c.id
        JOIN packages p ON a.package_id = p.id
        JOIN client_packages cp ON cp.client_id = c.id AND cp.package_id = p.id
        WHERE DATE(a.check_in_time) = ?
        ORDER BY a.check_in_time DESC
    ");
    $stmt->execute([$search_date]);
    $attendance_history = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .gym-sections {
            display: grid;
            gap: 20px;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .assign-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .assign-form .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .attendance-search {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .debt-indicator {
            display: inline-block;
            margin-left: 10px;
            font-size: 20px;
        }
        
        .search-results {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }
        
        .client-result {
            padding: 10px;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .client-result:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Gym Management</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="gym-sections">
            <!-- Assign Monthly Package -->
            <div class="section">
                <h2>Assign Monthly Package</h2>
                <form class="assign-form" onsubmit="assignPackage(event)">
                    <div class="form-group">
                        <label>Search Client</label>
                        <input type="text" id="clientSearch" placeholder="Name or phone..." onkeyup="searchClient()">
                        <div id="searchResults" class="search-results"></div>
                        <input type="hidden" id="selectedClientId">
                    </div>
                    
                    <div class="form-group">
                        <label>Select Package</label>
                        <select id="packageSelect" required>
                            <option value="">Choose package...</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?php echo $package['id']; ?>" data-duration="<?php echo $package['duration']; ?>">
                                    <?php echo htmlspecialchars($package['name']); ?> - 
                                    <?php echo $package['currency'] == 'USD' ? '$' : 'L.L. '; ?>
                                    <?php echo number_format($package['price'], $package['currency'] == 'USD' ? 2 : 0); ?>
                                    (<?php echo $package['duration']; ?> days)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">Assign Package</button>
                </form>
            </div>
            
            <!-- Attendance Today -->
            <div class="section">
                <h2>Attendance Today</h2>
                <div class="attendance-search">
                    <input type="text" id="attendanceSearch" placeholder="Search client name or phone...">
                    <button onclick="markAttendance()" class="btn-success">Mark Attendance</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Client Name</th>
                            <th>Package</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTable">
                        <?php foreach ($attendance_today as $att): ?>
                        <tr>
                            <td><?php echo date('h:i A', strtotime($att['check_in_time'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($att['client_name']); ?>
                                <?php
                                // Check if client has debt
                                $debtStmt = $pdo->prepare("SELECT COUNT(*) as debt_count FROM debts WHERE client_id = ? AND paid = 0");
                                $debtStmt->execute([$att['client_id']]);
                                $hasDebt = $debtStmt->fetch()['debt_count'] > 0;
                                if ($hasDebt): ?>
                                    <span class="debt-indicator" title="Has unpaid debt">💰</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($att['package_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($att['start_date'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($att['end_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $att['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($att['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="sendWhatsApp('<?php echo $att['phone']; ?>', '<?php echo $att['client_name']; ?>')" class="whatsapp-btn">WhatsApp</button>
                                <button onclick="deleteAttendance(<?php echo $att['id']; ?>)" class="delete-btn">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Attendance History -->
            <div class="section">
                <h2>Attendance History</h2>
                <div class="attendance-search">
                    <input type="date" id="historyDate" value="<?php echo $search_date; ?>" onchange="searchHistory()">
                    <button onclick="searchHistory()" class="btn-primary">Search</button>
                </div>
                
                <?php if (isset($attendance_history)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Client Name</th>
                            <th>Package</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_history as $att): ?>
                        <tr>
                            <td><?php echo date('h:i A', strtotime($att['check_in_time'])); ?></td>
                            <td><?php echo htmlspecialchars($att['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($att['package_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $att['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($att['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function searchClient() {
            const search = document.getElementById('clientSearch').value;
            const resultsDiv = document.getElementById('searchResults');
            
            if (search.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            
            fetch('api/search_clients.php?q=' + encodeURIComponent(search))
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultsDiv.style.display = 'block';
                        data.forEach(client => {
                            const div = document.createElement('div');
                            div.className = 'client-result';
                            div.innerHTML = `${client.name} - ${client.phone}`;
                            div.onclick = () => selectClient(client);
                            resultsDiv.appendChild(div);
                        });
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        }
        
        function selectClient(client) {
            document.getElementById('clientSearch').value = client.name + ' - ' + client.phone;
            document.getElementById('selectedClientId').value = client.id;
            document.getElementById('searchResults').style.display = 'none';
        }
        
        function assignPackage(event) {
            event.preventDefault();
            const clientId = document.getElementById('selectedClientId').value;
            const packageId = document.getElementById('packageSelect').value;
            
            if (!clientId || !packageId) {
                alert('Please select a client and package');
                return;
            }
            
            const formData = new FormData();
            formData.append('client_id', clientId);
            formData.append('package_id', packageId);
            
            fetch('actions/assign_package.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Package assigned successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function markAttendance() {
            const search = document.getElementById('attendanceSearch').value;
            if (!search) {
                alert('Please enter client name or phone');
                return;
            }
            
            fetch('actions/mark_attendance.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({search: search})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance marked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function sendWhatsApp(phone, name) {
            const message = `Hello ${name}, your gym subscription is about to expire. Please renew to continue enjoying our services!`;
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
        }
        
        function deleteAttendance(id) {
            if (confirm('Are you sure you want to delete this attendance record?')) {
                fetch('actions/delete_attendance.php', {
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
        
        function searchHistory() {
            const date = document.getElementById('historyDate').value;
            window.location.href = 'gym.php?date=' + date;
        }
    </script>
</body>
</html>
