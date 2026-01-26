# Scam Report Hub

A web application for reporting, browsing, and checking scams. Users can submit scam reports, browse existing reports, and check phone numbers, emails, and websites to help prevent fraud and protect the community.

**Live Website:** [scamreporthub.com](https://scamreporthub.com)

## Project Structure

```
.
├── config.php.example          # Configuration template (copy to config.php)
├── public_html/                # Main web application files
│   ├── admin_*.php            # Admin panel files
│   ├── browse.php             # Browse scams page
│   ├── index.php              # Homepage
│   ├── report.php             # Report a scam page
│   ├── scam.php               # Individual scam detail page
│   ├── submit_*.php           # Form submission handlers
│   ├── includes/              # Shared PHP includes
│   │   ├── db.php            # Database connection
│   │   ├── navbar.php        # Navigation bar
│   │   └── footer.php        # Footer
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   ├── images/                # Static images
│   └── uploads/               # User-uploaded files (excluded from git)
├── database_backups/           # SQL backups (excluded from git)
└── public_html/community/      # WordPress installation (excluded from git)
```

## Requirements

- PHP 8.3 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Google reCAPTCHA v2 API keys

## Setup Instructions

### 1. Clone the Repository

```bash
git clone <repository-url>
cd scam-report-hub
```

### 2. Configure Database

1. Create a MySQL database for the application
2. Import your database schema (if you have a SQL dump)

### 3. Configure Application

1. Copy the configuration template:
   ```bash
   cp config.php.example config.php
   ```

2. Edit `config.php` with your actual values:
   - Database host, name, username, and password
   - Google reCAPTCHA secret key
   - Any other site-specific settings

3. **Important:** The `config.php` file should be located **outside** the web root for security. On the production server, it's typically placed in a directory above `public_html`.

4. Update the absolute path in `public_html/includes/db.php` to point to your `config.php` file location:
   ```php
   require_once '/path/to/your/config.php';
   ```

### 4. Set Up Web Server

Point your web server's document root to the `public_html/` directory.

**Apache Example (.htaccess is included):**
- Document root: `/path/to/project/public_html`

**Nginx Example:**
```nginx
server {
    root /path/to/project/public_html;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 5. Set Permissions

Ensure the `uploads/` directory is writable by the web server:
```bash
chmod 755 public_html/uploads
```

### 6. Google reCAPTCHA Setup

1. Register your site at [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
2. Get your Site Key and Secret Key
3. Add the Site Key to your frontend forms (check existing forms for implementation)
4. Add the Secret Key to `config.php`

## Configuration Files

### config.php

The main configuration file contains:
- Database connection credentials
- Google reCAPTCHA secret key
- Optional site settings

**Security Note:** This file is excluded from version control. Always keep it outside the web root when possible.

### Database Connection

The application uses PDO for database connections with the following security features:
- Prepared statements (emulation disabled)
- Exception-based error handling
- Production-safe error messages

## Features

- **Report Scams:** Users can submit scam reports with details, images, and evidence
- **Browse Reports:** Search and filter scam reports
- **Admin Panel:** Moderate and manage scam reports
- **CSRF Protection:** Security tokens on all forms
- **File Uploads:** Secure handling of user-uploaded evidence
- **SEO Optimized:** Sitemap generation and meta tags

## WordPress Installation

**Note:** This repository does not include WordPress files. The WordPress installation in `public_html/community/` is managed separately and is excluded from version control.

If you need to set up WordPress:
1. Install WordPress separately in the `public_html/community/` directory
2. Configure `wp-config.php` with your WordPress database credentials
3. The WordPress installation is independent of the main Scam Report Hub application

## Excluded Files

The following are excluded from version control (see `.gitignore`):

- `config.php` - Contains sensitive credentials
- `database_backups/` - SQL backup files
- `public_html/uploads/` - User-uploaded content
- `public_html/community/` - WordPress installation
- `manifest.json` - Server configuration backup
- `.bash_profile` - Server-specific shell config
- Log files, cache files, and temporary files

## Development

### File Structure Notes

- All main application files are in `public_html/`
- Shared includes (database, navigation, footer) are in `public_html/includes/`
- Static assets (CSS, JS, images) are organized in their respective directories
- Admin functionality is prefixed with `admin_` in filenames

### Security Considerations

- Configuration files with credentials are excluded from git
- Database connections use PDO with prepared statements
- CSRF tokens protect all forms
- File uploads should be validated and sanitized
- Error messages don't expose sensitive information in production

## License

[Add your license information here]

## Contributing

[Add contribution guidelines if applicable]

## Support

For issues and questions, please [create an issue](link-to-issues) or contact [your contact information].
