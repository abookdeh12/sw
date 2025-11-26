<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!hasAccess('social_media')) {
    die('Access denied');
}

// Get client counts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$total_clients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT c.id) as total FROM clients c 
                     JOIN client_packages cp ON c.id = cp.client_id 
                     WHERE cp.status = 'active'");
$active_clients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT c.id) as total FROM clients c 
                     JOIN client_packages cp ON c.id = cp.client_id 
                     WHERE cp.status = 'expired'");
$expired_clients = $stmt->fetch()['total'];

// Get recent WhatsApp logs
$stmt = $pdo->query("SELECT w.*, c.name as client_name 
                     FROM whatsapp_logs w 
                     JOIN clients c ON w.client_id = c.id 
                     ORDER BY w.sent_date DESC 
                     LIMIT 50");
$whatsapp_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media - Gym System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .social-container {
            display: grid;
            gap: 20px;
        }
        
        .messaging-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .target-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-box .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .message-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .target-selection {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .target-option {
            flex: 1;
            padding: 15px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .target-option:hover {
            border-color: #667eea;
        }
        
        .target-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .logs-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .log-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .preview-section {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .preview-header {
            font-weight: bold;
            margin-bottom: 10px;
            color: #1976d2;
        }
        
        .send-progress {
            display: none;
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Social Media Integration</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                <a href="auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="social-container">
            <!-- WhatsApp Messaging -->
            <div class="messaging-section">
                <h2>WhatsApp Bulk Messaging</h2>
                
                <div class="target-stats">
                    <div class="stat-box">
                        <div class="number"><?php echo $total_clients; ?></div>
                        <div class="label">All Clients</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $active_clients; ?></div>
                        <div class="label">Active Members</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $expired_clients; ?></div>
                        <div class="label">Expired Members</div>
                    </div>
                </div>
                
                <div class="message-form">
                    <h3>Compose Message</h3>
                    
                    <div class="form-group">
                        <label>Select Target Group</label>
                        <div class="target-selection">
                            <div class="target-option" onclick="selectTarget('all')" id="target-all">
                                <strong>All Clients</strong>
                                <div style="font-size: 12px; margin-top: 5px;">Send to everyone</div>
                            </div>
                            <div class="target-option" onclick="selectTarget('active')" id="target-active">
                                <strong>Active Only</strong>
                                <div style="font-size: 12px; margin-top: 5px;">Current members</div>
                            </div>
                            <div class="target-option" onclick="selectTarget('expired')" id="target-expired">
                                <strong>Expired Only</strong>
                                <div style="font-size: 12px; margin-top: 5px;">Renewal reminders</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Message Template</label>
                        <select id="messageTemplate" onchange="loadTemplate()">
                            <option value="">Custom Message</option>
                            <option value="renewal">Renewal Reminder</option>
                            <option value="promotion">Special Promotion</option>
                            <option value="announcement">General Announcement</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Message Text</label>
                        <textarea id="messageText" rows="6" placeholder="Type your message here...">Hello {name},

Your gym membership is expiring soon. Don't miss out on your fitness journey!

Renew now and get 10% off on your next package.

Best regards,
Gym Team</textarea>
                    </div>
                    
                    <div class="preview-section">
                        <div class="preview-header">Preview (for John Doe)</div>
                        <div id="messagePreview"></div>
                    </div>
                    
                    <button onclick="sendBulkMessages()" class="btn-success" style="margin-top: 15px;">
                        Send WhatsApp Messages
                    </button>
                    
                    <div id="sendProgress" class="send-progress">
                        <div>Sending messages... <span id="progressText">0/0</span></div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Message Logs -->
            <div class="logs-section">
                <h2>Recent Message History</h2>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($whatsapp_logs as $log): ?>
                    <div class="log-item">
                        <div>
                            <strong><?php echo htmlspecialchars($log['client_name']); ?></strong>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                Target: <?php echo ucfirst($log['target_group']); ?> | 
                                <?php echo date('Y-m-d H:i', strtotime($log['sent_date'])); ?>
                            </div>
                        </div>
                        <div>
                            <button onclick="viewMessage('<?php echo htmlspecialchars($log['message']); ?>')" 
                                    class="btn-primary" style="padding: 4px 8px; font-size: 12px;">
                                View
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedTarget = null;
        
        function selectTarget(target) {
            selectedTarget = target;
            document.querySelectorAll('.target-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('target-' + target).classList.add('selected');
            updatePreview();
        }
        
        function loadTemplate() {
            const template = document.getElementById('messageTemplate').value;
            const messageText = document.getElementById('messageText');
            
            const templates = {
                renewal: `Hello {name},

Your gym membership is expiring soon. Don't miss out on your fitness journey!

Renew now and get 10% off on your next package.

Best regards,
Gym Team`,
                promotion: `Hello {name},

🎉 Special Offer Just for You! 🎉

Join us this month and get 20% OFF on all packages!
Limited time offer - Don't miss out!

Call us now or visit the gym for more details.

Stay fit, stay healthy!
Gym Team`,
                announcement: `Hello {name},

Important Announcement:

We have new gym timings starting next week:
Monday-Friday: 6 AM - 10 PM
Saturday-Sunday: 7 AM - 8 PM

Thank you for being a valued member!

Gym Team`
            };
            
            if (template && templates[template]) {
                messageText.value = templates[template];
                updatePreview();
            }
        }
        
        function updatePreview() {
            const message = document.getElementById('messageText').value;
            const preview = document.getElementById('messagePreview');
            preview.textContent = message.replace('{name}', 'John Doe');
        }
        
        function sendBulkMessages() {
            if (!selectedTarget) {
                alert('Please select a target group');
                return;
            }
            
            const message = document.getElementById('messageText').value;
            if (!message.trim()) {
                alert('Please enter a message');
                return;
            }
            
            if (!confirm(`Are you sure you want to send this message to ${selectedTarget} clients?`)) {
                return;
            }
            
            // Show progress
            document.getElementById('sendProgress').style.display = 'block';
            
            // Simulate sending messages
            fetch('actions/send_whatsapp_bulk.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    target: selectedTarget,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Messages sent successfully to ${data.count} clients!`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
                document.getElementById('sendProgress').style.display = 'none';
            });
        }
        
        function viewMessage(message) {
            alert(message);
        }
        
        // Update preview on text change
        document.getElementById('messageText').addEventListener('input', updatePreview);
        
        // Initial preview
        updatePreview();
    </script>
</body>
</html>
