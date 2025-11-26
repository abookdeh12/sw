<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('clients')) {
    die('Access denied');
}

// Get total clients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$total_clients = $stmt->fetch()['total'];

// Get expired clients
$stmt = $pdo->query("SELECT COUNT(DISTINCT c.id) as total FROM clients c 
                     JOIN client_packages cp ON c.id = cp.client_id 
                     WHERE cp.status = 'expired'");
$expired_clients = $stmt->fetch()['total'];

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = '';
$params = [];

if ($search) {
    $searchQuery = " WHERE c.name LIKE ? OR c.phone LIKE ? OR c.referral LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Get all clients
$stmt = $pdo->prepare("
    SELECT c.*, 
           cp.end_date as membership_ends,
           cp.status
    FROM clients c
    LEFT JOIN (
        SELECT client_id, MAX(end_date) as end_date, status
        FROM client_packages
        GROUP BY client_id
    ) cp ON c.id = cp.client_id
    $searchQuery
    ORDER BY c.id DESC
");
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .clients-section {
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
        }
        
        .btn-add-client {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .referral-checkbox {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .referral-checkbox label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .referral-checkbox input {
            width: auto;
            margin-right: 5px;
        }
        
        #otherReferralText {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Clients</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_clients; ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $expired_clients; ?></div>
                <div class="stat-label">Expired Total</div>
            </div>
        </div>
        
        <div class="clients-section">
            <div class="section-header">
                <h2>Client List</h2>
                <button class="btn-add-client" onclick="showAddClientModal()">Add Client</button>
            </div>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by name, phone, or referral..." value="<?php echo htmlspecialchars($search); ?>">
                <button onclick="searchClients()">Search</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Family Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Referral</th>
                        <th>Note</th>
                        <th>Membership Ends</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($clients as $client): 
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                        <td><?php echo htmlspecialchars($client['family_name']); ?></td>
                        <td><?php echo htmlspecialchars($client['phone']); ?></td>
                        <td><?php echo ucfirst($client['gender']); ?></td>
                        <td><?php echo htmlspecialchars($client['referral'] ?: $client['referral_other']); ?></td>
                        <td><?php echo htmlspecialchars($client['note']); ?></td>
                        <td><?php echo $client['membership_ends'] ? date('Y-m-d', strtotime($client['membership_ends'])) : '-'; ?></td>
                        <td>
                            <?php if ($client['status']): ?>
                                <span class="badge badge-<?php echo $client['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($client['status']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">No Package</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddClientModal()">&times;</span>
            <h2>Add New Client</h2>
            <form action="actions/add_client.php" method="POST">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Family Name</label>
                    <input type="text" name="family_name">
                </div>
                
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>How did you hear about us?</label>
                    <div class="referral-checkbox">
                        <label><input type="radio" name="referral" value="Instagram"> Instagram</label>
                        <label><input type="radio" name="referral" value="Facebook"> Facebook</label>
                        <label><input type="radio" name="referral" value="Roof"> Roof</label>
                        <label><input type="radio" name="referral" value="WOM"> Word of Mouth</label>
                        <label><input type="radio" name="referral" value="Board"> Board</label>
                        <label><input type="radio" name="referral" value="Other" onclick="toggleOtherReferral()"> Other</label>
                    </div>
                    <input type="text" name="referral_other" id="otherReferralText" placeholder="Please specify...">
                </div>
                
                <div class="form-group">
                    <label>Note</label>
                    <textarea name="note" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn-primary">Add Client</button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddClientModal() {
            document.getElementById('addClientModal').style.display = 'block';
        }
        
        function closeAddClientModal() {
            document.getElementById('addClientModal').style.display = 'none';
        }
        
        function toggleOtherReferral() {
            var otherText = document.getElementById('otherReferralText');
            if (document.querySelector('input[name="referral"]:checked').value === 'Other') {
                otherText.style.display = 'block';
                otherText.required = true;
            } else {
                otherText.style.display = 'none';
                otherText.required = false;
            }
        }
        
        function searchClients() {
            var search = document.getElementById('searchInput').value;
            window.location.href = 'clients.php?search=' + encodeURIComponent(search);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('addClientModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchClients();
            }
        });
    </script>
</body>
</html>
