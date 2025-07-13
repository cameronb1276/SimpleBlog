#!/bin/bash

# SimpleBlog Complete Production Setup Script for Ubuntu
# This script installs dependencies, configures SimpleBlog, and sets up phpMyAdmin

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${SCRIPT_DIR}/backup-$(date +%Y%m%d-%H%M%S)"
WEBROOT="/var/www/html"
BLOG_DIR_NAME=""
BLOG_FULL_PATH=""
SITE_TITLE=""
SITE_DESCRIPTION=""
ADMIN_USERNAME=""
ADMIN_PASSWORD=""
ADMIN_EMAIL=""
DB_HOST="localhost"
DB_NAME=""
DB_USER=""
DB_PASS=""
DB_ROOT_PASS=""
PHPMYADMIN_USER=""
PHPMYADMIN_PASS=""
DOMAIN_NAME=""

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to read input with default value
read_with_default() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    
    if [ -n "$default" ]; then
        echo -n "$prompt [$default]: "
    else
        echo -n "$prompt: "
    fi
    
    read input
    if [ -z "$input" ] && [ -n "$default" ]; then
        input="$default"
    fi
    
    eval "$var_name=\"\$input\""
}

# Function to read password securely
read_password() {
    local prompt="$1"
    local var_name="$2"
    
    echo -n "$prompt: "
    read -s password
    echo
    eval "$var_name=\"\$password\""
}

# Function to generate random password
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Function to validate email
validate_email() {
    local email="$1"
    if [[ $email =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
        return 0
    else
        return 1
    fi
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if package is installed
package_installed() {
    dpkg -l | grep -q "^ii  $1 "
}

# Function to install Apache
install_apache() {
    print_status "Installing Apache web server..."
    
    apt update
    apt install -y apache2
    
    # Enable Apache modules
    a2enmod rewrite
    a2enmod ssl
    
    # Start and enable Apache
    systemctl start apache2
    systemctl enable apache2
    
    print_success "Apache installed and configured"
}

# Function to install PHP and dependencies
install_php() {
    print_status "Installing PHP and required extensions..."
    
    apt install -y php php-mysql php-mbstring php-zip php-gd php-json php-curl php-xml php-intl php-bcmath
    
    # Restart Apache to load PHP
    systemctl restart apache2
    
    print_success "PHP and extensions installed"
}

# Function to install MySQL
install_mysql() {
    print_status "Installing MySQL server..."
    
    # Set non-interactive mode for MySQL installation
    export DEBIAN_FRONTEND=noninteractive
    
    # Pre-configure MySQL root password
    echo "mysql-server mysql-server/root_password password $DB_ROOT_PASS" | debconf-set-selections
    echo "mysql-server mysql-server/root_password_again password $DB_ROOT_PASS" | debconf-set-selections
    
    apt install -y mysql-server
    
    # Start and enable MySQL
    systemctl start mysql
    systemctl enable mysql
    
    # Secure MySQL installation (automated)
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='';"
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -u root -p"$DB_ROOT_PASS" -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -u root -p"$DB_ROOT_PASS" -e "FLUSH PRIVILEGES;"
    
    print_success "MySQL installed and secured"
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
    
    # Check Ubuntu version
    if ! grep -q "Ubuntu" /etc/os-release; then
        print_warning "This script is designed for Ubuntu. Proceeding anyway..."
    fi
    
    # Check internet connection
    if ! ping -c 1 google.com &> /dev/null; then
        print_error "No internet connection. Please check your network."
        exit 1
    fi
    
    print_success "System requirements check passed"
}

# Function to install dependencies
install_dependencies() {
    print_status "Installing system dependencies..."
    
    # Update package list
    apt update
    
    # Install basic tools
    apt install -y wget unzip curl software-properties-common
    
    # Check and install Apache
    if ! package_installed "apache2"; then
        install_apache
    else
        print_success "Apache already installed"
    fi
    
    # Check and install PHP
    if ! command_exists "php"; then
        install_php
    else
        print_success "PHP already installed"
    fi
    
    # Check and install MySQL
    if ! package_installed "mysql-server"; then
        install_mysql
    else
        print_success "MySQL already installed"
    fi
    
    print_success "All dependencies installed"
}

# Function to setup MySQL database and user
setup_mysql() {
    print_status "Setting up MySQL database and user..."
    
    # Create database
    mysql -u root -p"$DB_ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Create database user
    mysql -u root -p"$DB_ROOT_PASS" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -u root -p"$DB_ROOT_PASS" -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
    mysql -u root -p"$DB_ROOT_PASS" -e "FLUSH PRIVILEGES;"
    
    print_success "MySQL database and user created"
}

# Function to download and setup phpMyAdmin
setup_phpmyadmin() {
    print_status "Downloading and setting up phpMyAdmin..."
    
    cd /tmp
    
    # Download phpMyAdmin
    wget -q "https://files.phpmyadmin.net/phpMyAdmin/5.2.2/phpMyAdmin-5.2.2-all-languages.zip" -O phpmyadmin.zip
    
    # Extract phpMyAdmin
    unzip -q phpmyadmin.zip
    
    # Move to web directory
    mv phpMyAdmin-5.2.2-all-languages "$WEBROOT/phpmyadmin"
    
    # Set permissions
    chown -R www-data:www-data "$WEBROOT/phpmyadmin"
    chmod -R 755 "$WEBROOT/phpmyadmin"
    
    # Create phpMyAdmin configuration
    cat > "$WEBROOT/phpmyadmin/config.inc.php" << EOF
<?php
\$cfg['blowfish_secret'] = '$(generate_password)';
\$i = 0;
\$i++;
\$cfg['Servers'][\$i]['auth_type'] = 'cookie';
\$cfg['Servers'][\$i]['host'] = 'localhost';
\$cfg['Servers'][\$i]['compress'] = false;
\$cfg['Servers'][\$i]['AllowNoPassword'] = false;
\$cfg['UploadDir'] = '';
\$cfg['SaveDir'] = '';
\$cfg['TempDir'] = '/tmp';
?>
EOF
    
    # Clean up
    rm -f /tmp/phpmyadmin.zip
    rm -rf /tmp/phpMyAdmin-5.2.2-all-languages
    
    print_success "phpMyAdmin installed and configured"
}

# Function to copy SimpleBlog files
copy_simpleblog_files() {
    print_status "Copying SimpleBlog files to web directory..."
    
    # Create blog directory
    mkdir -p "$BLOG_FULL_PATH"
    
    # Copy all files except setup scripts and git files
    rsync -av --exclude='setup*.sh' --exclude='.git*' --exclude='*.md' "$SCRIPT_DIR/" "$BLOG_FULL_PATH/"
    
    # Create uploads directory if it doesn't exist
    mkdir -p "$BLOG_FULL_PATH/assets/uploads"
    
    # Set proper permissions
    chown -R www-data:www-data "$BLOG_FULL_PATH"
    chmod -R 755 "$BLOG_FULL_PATH"
    chmod -R 775 "$BLOG_FULL_PATH/assets/uploads"
    
    print_success "SimpleBlog files copied successfully"
}

# Function to create config.php
create_config_php() {
    print_status "Creating configuration file..."
    
    # Create includes directory if it doesn't exist
    mkdir -p "$BLOG_FULL_PATH/includes"
    
    # Create config.php
    cat > "$BLOG_FULL_PATH/includes/config.php" << EOF
<?php
// SimpleBlog Configuration File
// Generated by setup script on $(date)

// Database Configuration
define('DB_HOST', '$DB_HOST');
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');

// Site Configuration
define('SITE_URL', 'http://$DOMAIN_NAME/$BLOG_DIR_NAME');
define('ADMIN_URL', 'http://$DOMAIN_NAME/$BLOG_DIR_NAME/admin');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// File Upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('UPLOAD_PATH', 'assets/uploads/');

// Database connection
try {
    \$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die("Connection failed: " . \$e->getMessage());
}
?>
EOF
    
    chmod 640 "$BLOG_FULL_PATH/includes/config.php"
    chown www-data:www-data "$BLOG_FULL_PATH/includes/config.php"
    
    print_success "Configuration file created"
}

# Function to setup database schema
setup_database_schema() {
    print_status "Setting up database schema..."
    
    # Create database tables
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" << 'EOF'
-- SimpleBlog Database Schema
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_name VARCHAR(100) UNIQUE NOT NULL,
    option_value TEXT,
    INDEX idx_option_name (option_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOF
    
    # Insert default settings
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" -e "
        INSERT IGNORE INTO settings (option_name, option_value) VALUES
        ('site_title', '$SITE_TITLE'),
        ('site_description', '$SITE_DESCRIPTION'),
        ('active_theme', 'default'),
        ('posts_per_page', '10');"
    
    print_success "Database schema created"
}

# Function to create admin user
create_admin_user() {
    print_status "Creating admin user..."
    
    # Generate password hash using PHP
    local password_hash
    password_hash=$(php -r "echo password_hash('$ADMIN_PASSWORD', PASSWORD_DEFAULT);")
    
    # Insert admin user
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" -e "
        INSERT INTO users (username, email, password_hash) 
        VALUES ('$ADMIN_USERNAME', '$ADMIN_EMAIL', '$password_hash')
        ON DUPLICATE KEY UPDATE 
            email = VALUES(email),
            password_hash = VALUES(password_hash);"
    
    print_success "Admin user created"
}

# Function to configure Apache virtual host
configure_apache() {
    print_status "Configuring Apache virtual host..."
    
    # Create virtual host configuration
    cat > "/etc/apache2/sites-available/$BLOG_DIR_NAME.conf" << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot $BLOG_FULL_PATH
    
    <Directory $BLOG_FULL_PATH>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Enable rewrite for clean URLs
    RewriteEngine On
    
    ErrorLog \${APACHE_LOG_DIR}/${BLOG_DIR_NAME}_error.log
    CustomLog \${APACHE_LOG_DIR}/${BLOG_DIR_NAME}_access.log combined
</VirtualHost>
EOF
    
    # Enable the site
    a2ensite "$BLOG_DIR_NAME.conf"
    
    # Disable default site if this is the main domain
    if [ "$BLOG_DIR_NAME" = "blog" ] || [ "$BLOG_DIR_NAME" = "www" ]; then
        a2dissite 000-default.conf
    fi
    
    # Reload Apache
    systemctl reload apache2
    
    print_success "Apache virtual host configured"
}

# Function to collect user input
collect_user_input() {
    echo "========================================"
    echo "    SimpleBlog Setup Configuration"
    echo "========================================"
    echo
    
    # Blog directory name
    read_with_default "Enter blog directory name" "simpleblog" BLOG_DIR_NAME
    BLOG_FULL_PATH="$WEBROOT/$BLOG_DIR_NAME"
    
    # Domain name
    read_with_default "Enter domain name (e.g., example.com or localhost)" "localhost" DOMAIN_NAME
    
    # Site information
    read_with_default "Enter site title" "My SimpleBlog" SITE_TITLE
    read_with_default "Enter site description" "A simple blog powered by SimpleBlog" SITE_DESCRIPTION
    
    # Database credentials
    echo
    print_status "Database Configuration"
    read_with_default "Enter MySQL root password" "" DB_ROOT_PASS
    read_with_default "Enter database name" "${BLOG_DIR_NAME}_db" DB_NAME
    read_with_default "Enter database username" "${BLOG_DIR_NAME}_user" DB_USER
    
    # Generate or ask for database password
    local generated_db_pass
    generated_db_pass=$(generate_password)
    read_with_default "Enter database password" "$generated_db_pass" DB_PASS
    
    # Admin credentials
    echo
    print_status "Admin User Configuration"
    read_with_default "Enter admin username" "admin" ADMIN_USERNAME
    
    while true; do
        read_with_default "Enter admin email" "" ADMIN_EMAIL
        if validate_email "$ADMIN_EMAIL"; then
            break
        else
            print_error "Invalid email format. Please try again."
        fi
    done
    
    # Generate or ask for admin password
    local generated_admin_pass
    generated_admin_pass=$(generate_password)
    read_with_default "Enter admin password" "$generated_admin_pass" ADMIN_PASSWORD
    
    echo
    print_status "Configuration Summary:"
    echo "Blog Directory: $BLOG_FULL_PATH"
    echo "Domain: $DOMAIN_NAME"
    echo "Site Title: $SITE_TITLE"
    echo "Database: $DB_NAME"
    echo "Database User: $DB_USER"
    echo "Admin Username: $ADMIN_USERNAME"
    echo "Admin Email: $ADMIN_EMAIL"
    echo
    
    echo "Proceed with installation? (y/n)"
    read -r confirm
    if [[ ! $confirm =~ ^[Yy]$ ]]; then
        print_status "Installation cancelled"
        exit 0
    fi
}

# Function to display final summary
display_summary() {
    echo
    print_success "SimpleBlog installation completed successfully!"
    echo
    echo "=== Installation Summary ==="
    echo "Blog URL: http://$DOMAIN_NAME/$BLOG_DIR_NAME"
    echo "Admin Panel: http://$DOMAIN_NAME/$BLOG_DIR_NAME/admin/"
    echo "phpMyAdmin: http://$DOMAIN_NAME/phpmyadmin/"
    echo
    echo "=== Credentials ==="
    echo "Admin Username: $ADMIN_USERNAME"
    echo "Admin Password: $ADMIN_PASSWORD"
    echo "Admin Email: $ADMIN_EMAIL"
    echo
    echo "Database Name: $DB_NAME"
    echo "Database User: $DB_USER"
    echo "Database Password: $DB_PASS"
    echo
    echo "=== Next Steps ==="
    echo "1. Visit your blog at: http://$DOMAIN_NAME/$BLOG_DIR_NAME"
    echo "2. Access admin panel at: http://$DOMAIN_NAME/$BLOG_DIR_NAME/admin/"
    echo "3. Manage database via phpMyAdmin at: http://$DOMAIN_NAME/phpmyadmin/"
    echo "4. Create your first blog post"
    echo
    print_warning "Important Security Notes:"
    echo "- Change default passwords after first login"
    echo "- Consider setting up SSL/HTTPS"
    echo "- Regularly backup your database"
    echo "- Keep all software updated"
    echo
    print_success "Installation complete! Enjoy your new SimpleBlog!"
}

# Main function
main() {
    clear
    echo "========================================"
    echo "    SimpleBlog Complete Setup"
    echo "========================================"
    echo "This script will install and configure SimpleBlog with all dependencies."
    echo
    
    # Check system requirements
    check_requirements
    
    # Collect user input
    collect_user_input
    
    echo
    print_status "Starting SimpleBlog installation..."
    
    # Install dependencies
    install_dependencies
    
    # Setup MySQL
    setup_mysql
    
    # Setup phpMyAdmin
    setup_phpmyadmin
    
    # Copy SimpleBlog files
    copy_simpleblog_files
    
    # Create configuration
    create_config_php
    
    # Setup database schema
    setup_database_schema
    
    # Create admin user
    create_admin_user
    
    # Configure Apache
    configure_apache
    
    # Display summary
    display_summary
}

# Run main function
main "$@"