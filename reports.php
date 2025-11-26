<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('reports')) {
    die('Access denied');
}

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get all clients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$all_clients = $stmt->fetch()['total'];

// Get active clients
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) as total FROM clients c 
                       JOIN client_packages cp ON c.id = cp.client_id 
                       WHERE cp.status = 'active'");
$stmt->execute();
$active_clients = $stmt->fetch()['total'];

// Get expired clients
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) as total FROM clients c 
                       JOIN client_packages cp ON c.id = cp.client_id 
                       WHERE cp.status = 'expired'");
$stmt->execute();
$expired_clients = $stmt->fetch()['total'];

// Get new members in date range
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clients 
                       WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$new_members = $stmt->fetch()['total'];

// Get daily revenue
$stmt = $pdo->prepare("
    SELECT 
        DATE(sale_date) as date,
        SUM(CASE WHEN currency = 'USD' THEN amount ELSE 0 END) as usd_total,
        SUM(CASE WHEN currency = 'LBP' THEN amount ELSE 0 END) as lbp_total
    FROM (
        SELECT sale_date, amount, currency FROM package_sales
        WHERE sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT sale_date, total_amount as amount, currency FROM pos_sales
        WHERE DATE(sale_date) BETWEEN ? AND ? AND refunded = 0
    ) as combined_sales
    GROUP BY DATE(sale_date)
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date, $start_date, $end_date]);
$daily_revenue = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .report-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .report-stat {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .date-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .date-filter input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .revenue-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .total-row {
            font-weight: bold;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Reports</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="reports-grid">
            <!-- All Clients -->
            <div class="report-card">
                <h3>All Clients</h3>
                <div class="report-stat"><?php echo $all_clients; ?></div>
                <div class="date-filter">
                    <button class="export-btn" onclick="exportReport('all_clients')">Export to Excel</button>
                </div>
            </div>
            
            <!-- Active Clients -->
            <div class="report-card">
                <h3>Active Clients</h3>
                <div class="report-stat"><?php echo $active_clients; ?></div>
                <div class="date-filter">
                    <button class="export-btn" onclick="exportReport('active_clients')">Export to Excel</button>
                </div>
            </div>
            
            <!-- Expired Clients -->
            <div class="report-card">
                <h3>Expired Clients</h3>
                <div class="report-stat"><?php echo $expired_clients; ?></div>
                <div class="date-filter">
                    <button class="export-btn" onclick="exportReport('expired_clients')">Export to Excel</button>
                </div>
            </div>
            
            <!-- New Members -->
            <div class="report-card">
                <h3>New Members</h3>
                <div class="report-stat"><?php echo $new_members; ?></div>
                <div class="date-filter">
                    <input type="date" id="newMemberStart" value="<?php echo $start_date; ?>">
                    <input type="date" id="newMemberEnd" value="<?php echo $end_date; ?>">
                    <button onclick="filterNewMembers()">Filter</button>
                </div>
                <button class="export-btn" onclick="exportReport('new_members')">Export to Excel</button>
            </div>
        </div>
        
        <!-- Daily Revenue Report -->
        <div class="revenue-table">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Daily Revenue Report</h2>
                <div class="date-filter">
                    <input type="date" id="revenueStart" value="<?php echo $start_date; ?>">
                    <input type="date" id="revenueEnd" value="<?php echo $end_date; ?>">
                    <button onclick="filterRevenue()">Filter</button>
                    <button class="export-btn" onclick="exportReport('daily_revenue')">Export to Excel</button>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>USD Total</th>
                        <th>LBP Total</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_usd = 0;
                    $total_lbp = 0;
                    foreach ($daily_revenue as $revenue): 
                        $total_usd += $revenue['usd_total'];
                        $total_lbp += $revenue['lbp_total'];
                    ?>
                    <tr>
                        <td><?php echo $revenue['date']; ?></td>
                        <td><?php echo date('l', strtotime($revenue['date'])); ?></td>
                        <td>$<?php echo number_format($revenue['usd_total'], 2); ?></td>
                        <td>L.L. <?php echo number_format($revenue['lbp_total'], 0); ?></td>
                        <td>
                            <button onclick="viewDetails('<?php echo $revenue['date']; ?>')" class="btn-primary" style="padding: 4px 8px; font-size: 12px;">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td>$<?php echo number_format($total_usd, 2); ?></td>
                        <td>L.L. <?php echo number_format($total_lbp, 0); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function exportReport(type) {
            const params = new URLSearchParams({
                type: type,
                start_date: document.getElementById('revenueStart')?.value || '<?php echo $start_date; ?>',
                end_date: document.getElementById('revenueEnd')?.value || '<?php echo $end_date; ?>'
            });
            window.location.href = 'exports/export_report.php?' + params.toString();
        }
        
        function filterNewMembers() {
            const start = document.getElementById('newMemberStart').value;
            const end = document.getElementById('newMemberEnd').value;
            window.location.href = `reports.php?start_date=${start}&end_date=${end}`;
        }
        
        function filterRevenue() {
            const start = document.getElementById('revenueStart').value;
            const end = document.getElementById('revenueEnd').value;
            window.location.href = `reports.php?start_date=${start}&end_date=${end}`;
        }
        
        function viewDetails(date) {
            window.location.href = `report_details.php?date=${date}`;
        }
    </script>
</body>
</html>
