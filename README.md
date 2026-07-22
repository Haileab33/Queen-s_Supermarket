# Queen's Supermarket Management System

A comprehensive web-based inventory and user management system for supermarket operations. Built with PHP, MySQL, and vanilla JavaScript, this application provides admin and user dashboards for managing inventory, tracking activity, and handling user accounts.

## Features

### Admin Dashboard
- **Inventory Management**: Add, edit, and delete inventory items with categories, quantities, prices, and descriptions
- **User Management**: Create and manage admin and staff accounts with role-based access control
- **Activity Log**: Track all system activities including logins, inventory changes, and account actions
- **Statistics Dashboard**: View real-time stats for inventory items, low stock alerts, inventory value, and admin accounts

### User Dashboard
- **Shopping Cart**: Browse inventory and add items to cart
- **Cart Management**: View cart contents, update quantities, and remove items
- **Checkout Process**: Complete purchases with order tracking

### Authentication System
- Secure login and registration with password hashing
- Role-based access control (Admin/User)
- Session management with activity logging
- Hardcoded admin credentials for initial setup

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Styling**: Custom CSS with responsive design

## Installation

### Prerequisites
- XAMPP/WAMP or any PHP server with MySQL
- MySQL database server

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/Haileab33/Queen-s_Supermarket.git
   cd Queen-s_Supermarket
   ```

2. **Configure Database**
   - Create a MySQL database named `queens_supermarket`
   - Update database credentials in `db.php` if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'queens_supermarket');
     ```

3. **Deploy to Web Server**
   - Place files in your web server's document root (e.g., `htdocs` for XAMPP)
   - Access the application via browser at `http://localhost/Queen-s_Supermarket`

4. **Default Admin Credentials**
   - Username: `1adimn`
   - Password: `1adimn`
   - *Note: Change these credentials in `db.php` after initial setup*

## Database Schema

The application automatically creates the following tables on first run:

- **users**: Stores user accounts with roles, personal details, and salary information
- **inventory**: Contains product catalog with categories, quantities, and pricing
- **activity_log**: Tracks all system activities and user actions
- **cart**: Manages user shopping cart items

## File Structure

```
Queen-s_Supermarket/
├── index.php          # Login and registration page
├── admin.php          # Admin dashboard with inventory and user management
├── dashboard.php      # User dashboard for shopping
├── api.php            # REST API endpoints for AJAX operations
├── db.php             # Database connection and configuration
├── logout.php         # Session termination
├── style.css          # Application styles
├── main.js            # Frontend JavaScript logic
└── README.md          # Project documentation
```

## Usage

### For Admins
1. Log in with admin credentials
2. Access the admin dashboard to manage inventory
3. Create new user accounts with appropriate roles
4. Monitor activity logs for system auditing
5. Track low stock items and inventory value

### For Users
1. Register a new account or log in
2. Browse available inventory items
3. Add items to shopping cart
4. Manage cart quantities
5. Complete checkout process

## Security Features

- Password hashing using bcrypt
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input sanitization and validation

## Development

### Adding New Features
- Backend logic: Modify PHP files in the root directory
- Frontend styling: Update `style.css`
- Client-side functionality: Edit `main.js`
- API endpoints: Add routes to `api.php`

### Database Modifications
- Schema changes should be added to the table creation logic in `db.php`
- Use prepared statements for all database queries

## License

This project is open source and available for educational purposes.

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests.

## Support

For issues or questions, please open an issue on the GitHub repository. 
