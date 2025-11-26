<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('dashboard')) {
    die('Access denied');
}

// Get today's package sales (USD)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM package_sales WHERE sale_date = CURDATE() AND currency = 'USD'");
$stmt->execute();
$package_sales_usd = $stmt->fetch()['total'];

// Get today's POS sales (LBP)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM pos_sales WHERE DATE(sale_date) = CURDATE() AND currency = 'LBP' AND refunded = 0");
$stmt->execute();
$pos_sales_lbp = $stmt->fetch()['total'];

// Get today's expenses
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_date = CURDATE() ORDER BY created_at DESC");
$stmt->execute();
$expenses = $stmt->fetchAll();

// Get latest sales details
$stmt = $pdo->prepare("
    SELECT 
        c.name as client_name,
        p.name as package_name,
        ps.amount,
        ps.currency,
        ps.sale_date,
        'package' as type
    FROM package_sales ps
    JOIN client_packages cp ON ps.client_package_id = cp.id
    JOIN clients c ON cp.client_id = c.id
    JOIN packages p ON cp.package_id = p.id
    WHERE ps.sale_date = CURDATE()
    
    UNION ALL
    
    SELECT 
        IFNULL(c.name, 'Walk-in') as client_name,
        i.name as package_name,
        pos.total_amount as amount,
        pos.currency,
        pos.sale_date,
        'pos' as type
    FROM pos_sales pos
    JOIN items i ON pos.item_id = i.id
    LEFT JOIN clients c ON pos.client_id = c.id
    WHERE DATE(pos.sale_date) = CURDATE() AND pos.refunded = 0
    
    ORDER BY sale_date DESC
");
$stmt->execute();
$latest_sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <h3>Package Sales (USD)</h3>
                    <span class="date">Today</span>
                </div>
                <div class="card-body">
                    <div class="amount">$<?php echo number_format($package_sales_usd, 2); ?></div>
                    <div class="description">Total package sales for today</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>POS Sales (LBP)</h3>
                    <span class="date">Today</span>
                </div>
                <div class="card-body">
                    <div class="amount">L.L. <?php echo number_format($pos_sales_lbp, 0); ?></div>
                    <div class="description">Total POS sales for today</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Expenses</h3>
                    <button class="btn-add-expense" onclick="showExpenseModal()">Add Expense</button>
                </div>
                <div class="card-body">
                    <div class="expense-list">
                        <?php foreach ($expenses as $expense): ?>
                            <div class="expense-item">
                                <div class="expense-desc"><?php echo htmlspecialchars($expense['description']); ?></div>
                                <div class="expense-amount">
                                    <?php echo $expense['currency'] == 'USD' ? '$' : 'L.L. '; ?>
                                    <?php echo number_format($expense['amount'], $expense['currency'] == 'USD' ? 2 : 0); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sales-details">
            <div class="section-header">
                <h2>Latest Sales Today</h2>
                <button class="btn-export" onclick="exportToExcel()">Export to Excel</button>
            </div>
            
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Item/Package</th>
                        <th>Amount</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_sales as $sale): ?>
                        <tr>
                            <td><?php echo date('h:i A', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo htmlspecialchars($sale['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['package_name']); ?></td>
                            <td>
                                <?php echo $sale['currency'] == 'USD' ? '$' : 'L.L. '; ?>
                                <?php echo number_format($sale['amount'], $sale['currency'] == 'USD' ? 2 : 0); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $sale['type'] == 'package' ? 'badge-primary' : 'badge-success'; ?>">
                                    <?php echo ucfirst($sale['type']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Expense Modal -->
    <div id="expenseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeExpenseModal()">&times;</span>
            <h2>Add Expense</h2>
            <form action="actions/add_expense.php" method="POST">
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency">
                        <option value="USD">USD</option>
                        <option value="LBP">LBP</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Add Expense</button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
