# Quotation Management System (QMS)

A complete web-based Quotation Management System built with pure Object-Oriented PHP, MySQL, Tailwind CSS, and vanilla JavaScript. Designed for shared hosting environments (cPanel) with no Composer or Node.js dependencies.

## Features

- **Authentication**: Role-based login (Admin/Staff) with secure password hashing
- **Client Management**: Add, edit, and manage clients
- **Quotation Management**: Create professional quotations with dynamic price tables
- **Dynamic Price Table**: Add/remove rows, auto-calculate totals with tax support
- **Quotation Status**: Track Draft → Sent → Accepted → Rejected
- **Preview & Print**: Print-friendly quotation preview matching professional style
- **Email Sending**: Send quotations via email with PDF attachment (supports PHPMailer or PHP mail())
- **SMTP Configuration**: Configurable SMTP settings from admin panel
- **Company Settings**: Manage company info, logo, terms & conditions
- **Dashboard**: Overview of quotations with status counts
- **Duplicate Quotations**: Quickly duplicate existing quotations
- **AJAX-Powered**: Smooth user experience with AJAX operations

## Requirements

- PHP 7.4+ or PHP 8.x
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx web server
- No Composer, Node.js, or npm required

## Installation

### 1. Upload Files
Upload all files to your web server (e.g., public_html/qms or www/qms)

### 2. Create Database
1. Open phpMyAdmin or MySQL
2. Create a new database (e.g., `qms_db`)
3. Import the `database.sql` file

### 3. Configure Database
Edit `config/database.php` and update your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'qms_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('BASE_URL', 'http://yourdomain.com/qms/');
```

### 4. Set Permissions
Make the `assets/uploads/` directory writable (chmod 755 or 777 depending on server)

### 5. Login
Default credentials:
- Email: `admin@qms.com`
- Password: `admin123`

**Important**: Change the default password after first login!

## File Structure

```
qms/
├── admin/          # Admin panel (dashboard, quotations, clients, settings)
├── ajax/           # AJAX handlers
├── assets/         # CSS, JS, uploads
├── classes/        # OOP PHP classes
├── config/         # Configuration files
├── includes/       # Header, footer, auth, functions
├── vendor/         # PHPMailer (optional, for SMTP)
└── database.sql    # Database schema
```

## Usage

1. **Add Clients**: Go to Clients → Add New Client
2. **Create Quotation**: Go to Quotations → Create New Quotation
3. **Add Items**: Use the dynamic table to add price items
4. **Preview/Print**: View quotation and use Print or Preview
5. **Send Email**: From quotation view, click "Send Email" to email client with PDF
6. **Configure Email**: Go to Settings → Email/SMTP Settings for SMTP configuration

## Email Configuration

### Option 1: PHP mail() (Default)
Works on most shared hosting without additional setup.

### Option 2: SMTP (Recommended)
1. Download [PHPMailer](https://github.com/PHPMailer/PHPMailer) manually
2. Extract to `vendor/PHPMailer/` with structure: `vendor/PHPMailer/src/PHPMailer.php`, etc.
3. Configure SMTP settings in Settings → Email/SMTP Settings

## Security Features

- Prepared statements with PDO
- CSRF protection on all forms
- Password hashing with bcrypt
- Session-based authentication
- Input sanitization and output escaping
- Role-based access control
- File upload validation

## Browser Support

- Chrome, Firefox, Safari, Edge (modern browsers)
- Print functionality works in all major browsers

## Notes

- This system uses Tailwind CSS via CDN (no build step required)
- All JavaScript is vanilla (no frameworks)
- PDF attachment in email uses HTML format (can be printed/saved as PDF by recipient)
- For true PDF generation, you can manually add TCPDF/FPDF to `vendor/TCPDF/`

## Support

For issues or questions, please refer to the specification document.

## License

This project is open-source for personal and commercial use.
