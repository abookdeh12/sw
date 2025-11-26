<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('pos')) {
    die('Access denied');
}

// Get all items
$stmt = $pdo->query("SELECT * FROM items WHERE stock > 0");
$items = $stmt->fetchAll();

// Get clients with debts
$stmt = $pdo->query("
    SELECT c.id, c.name, c.phone, 
           SUM(d.amount) as total_debt
    FROM clients c
    JOIN debts d ON c.id = d.client_id
    WHERE d.paid = 0
    GROUP BY c.id
");
$clients_with_debt = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pos-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .items-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .cart-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .item-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .item-card.out-of-stock {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8d7da;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #28a745;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .item-stock {
            font-size: 12px;
            color: #666;
        }
        
        .cart-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-total {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            margin-bottom: 20px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-control button {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }
        
        .debt-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .debt-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .debt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        
        .checkout-buttons {
            display: grid;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Point of Sale</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="pos-container">
            <div class="items-section">
                <h2>Items</h2>
                <div class="items-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card <?php echo $item['stock'] == 0 ? 'out-of-stock' : ''; ?>" 
                             onclick="addToCart(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">L.L. <?php echo number_format($item['price'], 0); ?></div>
                            <div class="item-stock">Stock: <?php echo $item['stock']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="cart-section">
                <h2>Current Cart</h2>
                <div class="cart-items" id="cartItems">
                    <!-- Cart items will be added here -->
                </div>
                
                <div class="cart-total" id="cartTotal">
                    Total: L.L. 0
                </div>
                
                <div class="form-group">
                    <label>Customer (Optional)</label>
                    <select id="customerSelect">
                        <option value="">Walk-in Customer</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, name, phone FROM clients ORDER BY name");
                        while ($client = $stmt->fetch()):
                        ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['name'] . ' - ' . $client['phone']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="checkout-buttons">
                    <button class="btn-success" onclick="checkout()">Checkout</button>
                    <button class="btn-secondary" onclick="clearCart()">Clear Cart</button>
                    <button class="btn-primary" onclick="addDebt()">Add as Debt</button>
                </div>
            </div>
        </div>
        
        <!-- Debts Section -->
        <div class="debt-section">
            <h3>Pending Debts</h3>
            <div class="debt-list">
                <?php foreach ($clients_with_debt as $debtor): ?>
                    <div class="debt-item">
                        <div>
                            <?php echo htmlspecialchars($debtor['name']); ?> 
                            <span style="color: #666; font-size: 12px;"><?php echo $debtor['phone']; ?></span>
                        </div>
                        <div>
                            <span style="color: #dc3545; font-weight: bold;">
                                L.L. <?php echo number_format($debtor['total_debt'], 0); ?>
                            </span>
                            <button onclick="payDebt(<?php echo $debtor['id']; ?>)" class="btn-success" style="margin-left: 10px; padding: 4px 8px; font-size: 12px;">
                                Pay
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        let cart = [];
        
        function addToCart(item) {
            if (item.stock == 0) {
                alert('This item is out of stock!');
                return;
            }
            
            const existingItem = cart.find(i => i.id === item.id);
            if (existingItem) {
                if (existingItem.quantity < item.stock) {
                    existingItem.quantity++;
                } else {
                    alert('Not enough stock!');
                }
            } else {
                cart.push({...item, quantity: 1});
            }
            
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartDiv = document.getElementById('cartItems');
            const totalDiv = document.getElementById('cartTotal');
            
            cartDiv.innerHTML = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item';
                itemDiv.innerHTML = `
                    <div>
                        <div>${item.name}</div>
                        <div style="font-size: 12px; color: #666;">L.L. ${Number(item.price).toLocaleString()}</div>
                    </div>
                    <div class="quantity-control">
                        <button onclick="changeQuantity(${index}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="changeQuantity(${index}, 1)">+</button>
                        <button onclick="removeFromCart(${index})" style="background: #dc3545; color: white;">×</button>
                    </div>
                `;
                cartDiv.appendChild(itemDiv);
            });
            
            totalDiv.textContent = `Total: L.L. ${total.toLocaleString()}`;
        }
        
        function changeQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
            } else if (newQuantity <= item.stock) {
                item.quantity = newQuantity;
                updateCartDisplay();
            } else {
                alert('Not enough stock!');
            }
        }
        
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
        
        function clearCart() {
            cart = [];
            updateCartDisplay();
        }
        
        function checkout() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            
            const customerId = document.getElementById('customerSelect').value;
            
            fetch('actions/process_sale.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    items: cart,
                    customer_id: customerId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Sale completed successfully!');
                    clearCart();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function addDebt() {
            const customerId = document.getElementById('customerSelect').value;
            if (!customerId) {
                alert('Please select a customer for debt!');
                return;
            }
            
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            
            fetch('actions/add_debt.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    items: cart,
                    customer_id: customerId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Debt added successfully!');
                    clearCart();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function payDebt(clientId) {
            if (confirm('Mark all debts as paid for this client?')) {
                fetch('actions/pay_debt.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({client_id: clientId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Debt paid successfully!');
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
