# Gym Management System

A comprehensive gym management system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- **Login System**: Secure authentication with admin and staff accounts
- **Dashboard**: Real-time sales tracking (USD packages & LBP POS), expense management
- **Client Management**: Add, search, and manage gym members
- **Gym Operations**: Package assignment, attendance tracking
- **POS System**: Item sales, debt management, refunds
- **Reports**: Comprehensive reporting with Excel export
- **Package & Item Management**: Create and manage gym packages and POS items
- **Social Media Integration**: WhatsApp messaging to clients
- **Settings**: Staff account management, theme customization, password changes

## Installation

### Requirements
- XAMPP (or any PHP/MySQL server)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge)

### Setup Instructions

1. **Install XAMPP**
   - Download and install XAMPP from https://www.apachefriends.org/
   - Start Apache and MySQL services

2. **Copy Files**
   - Copy the entire `gym_system` folder to `C:\xampp\htdocs\`

3. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `gym_system`
   - Import the database schema:
     - Click on the `gym_system` database
     - Go to Import tab
     - Choose file: `gym_system/database/gym_system.sql`
     - Click Go

4. **Configure Database Connection**
   - Open `gym_system/config/database.php`
   - Update if needed (default settings should work with XAMPP):
     ```php
     $host = 'localhost';
     $dbname = 'gym_system';
     $username = 'root';
     $password = '';
     ```

5. **Access the System**
   - Open your browser
   - Navigate to: http://localhost/gym_system/
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`

## Default Login

- **Admin Account**
  - Username: `admin`
  - Password: `admin123`
  - Full access to all features

## File Structure

```
gym_system/
├── actions/          # PHP action handlers
├── api/             # API endpoints
├── assets/          # CSS, JS, images
│   ├── css/
│   └── js/
├── auth/            # Authentication files
├── config/          # Configuration files
├── database/        # SQL schema
├── exports/         # Excel export handlers
├── includes/        # Shared components
├── index.php        # Login page
├── dashboard.php    # Main dashboard
├── clients.php      # Client management
├── gym.php          # Gym operations
├── pos.php          # Point of Sale
├── reports.php      # Reports
├── packages_items.php # Package/Item management
├── social_media.php # Social media integration
└── settings.php     # System settings
```

## Usage

### Creating Staff Accounts
1. Login as admin
2. Go to Settings
3. Click "Create Staff Account"
4. Enter username and password
5. Select page permissions
6. Click Create Account

### Adding Clients
1. Go to Clients page
2. Click "Add Client"
3. Fill in client details
4. Select referral source
5. Click Add Client

### Assigning Packages
1. Go to Gym page
2. Search for client
3. Select package
4. Click Assign Package

### POS Sales
1. Go to POS page
2. Click on items to add to cart
3. Select customer (optional)
4. Click Checkout or Add as Debt

### Generating Reports
1. Go to Reports page
2. Select report type
3. Set date range
4. Click Export to Excel

## Security Notes

- Change the default admin password immediately after installation
- Use strong passwords for all accounts
- Regular database backups recommended
- Keep XAMPP and PHP updated

## Troubleshooting

### Database Connection Error
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database `gym_system` exists

### Login Issues
- Clear browser cookies and cache
- Check if session directory is writable
- Verify user exists in database

### Permission Denied
- Check file permissions in XAMPP/htdocs
- Ensure PHP has write access to necessary directories

## Support

For issues or questions, please check the documentation or contact system administrator.

## License

This system is for internal use only.
