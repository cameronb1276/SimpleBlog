#!/bin/bash

# SimpleBlog Production Setup Script
# This script configures SimpleBlog for your server environment

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
INSTALL_DIR=""
SITE_TITLE=""
SITE_DESCRIPTION=""
ADMIN_USERNAME=""
ADMIN_PASSWORD=""
ADMIN_EMAIL=""
DB_HOST=""
DB_NAME=""
DB_USER=""
DB_PASS=""
CREATE_DB_USER="false"
ROOT_DB_PASS=""

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

# Function to escape sed special characters
escape_sed() {
    echo "$1" | sed 's/[[\\.\\*^$()+?{|]/\\\\&/g'
}

# Function to create database and user
create_database() {
    print_status "Creating database and user..."
    
    # Create database
    mysql -u root -p"$ROOT_DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ "$CREATE_DB_USER" = "true" ]; then
        # Create database user
        mysql -u root -p"$ROOT_DB_PASS" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASS';"
        mysql -u root -p"$ROOT_DB_PASS" -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'$DB_HOST';"
        mysql -u root -p"$ROOT_DB_PASS" -e "FLUSH PRIVILEGES;"
    fi
    
    print_success "Database and user created successfully"
}

# Function to import database schema
import_database_schema() {
    print_status "Creating database tables..."
    
    # Create the database schema
    cat << 'EOF' | mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME"
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

-- Insert default settings
INSERT IGNORE INTO settings (option_name, option_value) VALUES
('site_title', 'PLACEHOLDER_SITE_TITLE'),
('site_description', 'PLACEHOLDER_SITE_DESCRIPTION'),
('active_theme', 'default'),
('posts_per_page', '10');
EOF

    print_success "Database tables created successfully"
}

# Function to create admin user
create_admin_user() {
    print_status "Creating admin user..."
    
    # Generate password hash
    local password_hash
    password_hash=$(php -r "echo password_hash('$ADMIN_PASSWORD', PASSWORD_DEFAULT);")
    
    # Insert admin user
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" -e "
        INSERT INTO users (username, email, password_hash) 
        VALUES ('$ADMIN_USERNAME', '$ADMIN_EMAIL', '$password_hash')
        ON DUPLICATE KEY UPDATE 
            email = VALUES(email),
            password_hash = VALUES(password_hash);"
    
    print_success "Admin user created successfully"
}

# Function to configure files
configure_files() {
    print_status "Configuring application files..."
    
    # Update config.php
    local escaped_host escaped_name escaped_user escaped_pass
    escaped_host=$(escape_sed "$DB_HOST")
    escaped_name=$(escape_sed "$DB_NAME")
    escaped_user=$(escape_sed "$DB_USER")
    escaped_pass=$(escape_sed "$DB_PASS")
    
    sed -i "s/PLACEHOLDER_DB_HOST/$escaped_host/g" "$INSTALL_DIR/includes/config.php"
    sed -i "s/PLACEHOLDER_DB_NAME/$escaped_name/g" "$INSTALL_DIR/includes/config.php"
    sed -i "s/PLACEHOLDER_DB_USER/$escaped_user/g" "$INSTALL_DIR/includes/config.php"
    sed -i "s/PLACEHOLDER_DB_PASS/$escaped_pass/g" "$INSTALL_DIR/includes/config.php"
    
    # Update database settings
    local escaped_title escaped_description
    escaped_title=$(escape_sed "$SITE_TITLE")
    escaped_description=$(escape_sed "$SITE_DESCRIPTION")
    
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" -e "
        UPDATE settings SET option_value = '$escaped_title' WHERE option_name = 'site_title';
        UPDATE settings SET option_value = '$escaped_description' WHERE option_name = 'site_description';"
    
    print_success "Configuration files updated successfully"
}

# Function to set file permissions
set_permissions() {
    print_status "Setting file permissions..."
    
    # Set ownership to web server user
    if command_exists apache2; then
        WEB_USER="www-data"
    elif command_exists nginx; then
        WEB_USER="nginx"
    elif command_exists httpd; then
        WEB_USER="apache"
    else
        WEB_USER="www-data"
    fi
    
    # Set ownership and permissions
    chown -R "$WEB_USER:$WEB_USER" "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod -R 775 "$INSTALL_DIR/assets/uploads"
    chmod 640 "$INSTALL_DIR/includes/config.php"
    
    print_success "File permissions set successfully"
}

# Function to display summary
display_summary() {
    echo
    print_success "SimpleBlog installation completed successfully!"
    echo
    echo "=== Installation Summary ==="
    echo "Installation Directory: $INSTALL_DIR"
    echo "Site Title: $SITE_TITLE"
    echo "Site Description: $SITE_DESCRIPTION"
    echo "Database Host: $DB_HOST"
    echo "Database Name: $DB_NAME"
    echo "Database User: $DB_USER"
    echo "Admin Username: $ADMIN_USERNAME"
    echo "Admin Email: $ADMIN_EMAIL"
    echo
    echo "=== Next Steps ==="
    echo "1. Configure your web server to serve files from: $INSTALL_DIR"
    echo "2. Access the admin panel at: http://your-domain/admin/"
    echo "3. Login with username: $ADMIN_USERNAME"
    echo "4. Change the default theme if desired"
    echo "5. Create your first blog post"
    echo
    print_warning "Remember to:"
    echo "- Keep your admin credentials secure"
    echo "- Regularly backup your database"
    echo "- Keep SimpleBlog updated"
    echo
}

# Main setup function
main() {
    clear
    echo "========================================"
    echo "    SimpleBlog Production Setup"
    echo "========================================"
    echo "This script will help you set up SimpleBlog on your server."
    echo

    # Check if running as root for file operations
    if [ "$EUID" -ne 0 ]; then
        print_warning "Some operations may require sudo privileges."
    fi

    # Check required commands
    print_status "Checking system requirements..."
    
    for cmd in php mysql; do
        if ! command_exists "$cmd"; then
            print_error "$cmd is not installed. Please install it first."
            exit 1
        fi
    done
    
    print_success "System requirements check passed"
    echo

    # Get installation directory
    read_with_default "Enter installation directory" "/var/www/html/simpleblog" INSTALL_DIR
    
    # Validate installation directory
    if [ ! -d "$(dirname "$INSTALL_DIR")" ]; then
        print_error "Parent directory $(dirname "$INSTALL_DIR") does not exist"
        exit 1
    fi

    # Create installation directory if it doesn't exist
    if [ ! -d "$INSTALL_DIR" ]; then
        mkdir -p "$INSTALL_DIR"
        print_success "Created installation directory: $INSTALL_DIR"
    fi

    # Copy files to installation directory
    print_status "Copying SimpleBlog files..."
    cp -r "$SCRIPT_DIR"/* "$INSTALL_DIR"/
    rm -f "$INSTALL_DIR/setup.sh"  # Remove setup script from installation
    print_success "Files copied successfully"

    echo
    echo "=== Site Configuration ==="
    
    # Get site information
    read_with_default "Enter site title" "My SimpleBlog" SITE_TITLE
    read_with_default "Enter site description" "A modern blogging platform" SITE_DESCRIPTION
    
    echo
    echo "=== Admin User Configuration ==="
    
    # Get admin user information
    read_with_default "Enter admin username" "admin" ADMIN_USERNAME
    
    while true; do
        read_with_default "Enter admin email" "" ADMIN_EMAIL
        if validate_email "$ADMIN_EMAIL"; then
            break
        else
            print_error "Invalid email format. Please try again."
        fi
    done
    
    while true; do
        read_password "Enter admin password (leave empty to generate)" ADMIN_PASSWORD
        if [ -z "$ADMIN_PASSWORD" ]; then
            ADMIN_PASSWORD=$(generate_password)
            print_success "Generated password: $ADMIN_PASSWORD"
            break
        elif [ ${#ADMIN_PASSWORD} -lt 8 ]; then
            print_error "Password must be at least 8 characters long"
        else
            break
        fi
    done

    echo
    echo "=== Database Configuration ==="
    
    # Get database information
    read_with_default "Enter database host" "localhost" DB_HOST
    read_with_default "Enter database name" "simpleblog" DB_NAME
    read_with_default "Enter database username" "bloguser" DB_USER
    
    echo
    echo "Do you want to create a new database user? (y/n)"
    read -r create_user_response
    if [[ $create_user_response =~ ^[Yy]$ ]]; then
        CREATE_DB_USER="true"
        read_password "Enter password for database user (leave empty to generate)" DB_PASS
        if [ -z "$DB_PASS" ]; then
            DB_PASS=$(generate_password)
            print_success "Generated database password: $DB_PASS"
        fi
        read_password "Enter MySQL root password" ROOT_DB_PASS
    else
        read_password "Enter existing database user password" DB_PASS
    fi

    # Verify database connection
    print_status "Testing database connection..."
    if mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" -e "SELECT 1;" >/dev/null 2>&1; then
        print_success "Database connection successful"
    elif [ "$CREATE_DB_USER" = "true" ]; then
        print_status "Will create database user during setup"
    else
        print_error "Cannot connect to database. Please check your credentials."
        exit 1
    fi

    echo
    echo "=== Setup Summary ==="
    echo "Installation Directory: $INSTALL_DIR"
    echo "Site Title: $SITE_TITLE"
    echo "Admin Username: $ADMIN_USERNAME"
    echo "Admin Email: $ADMIN_EMAIL"
    echo "Database Host: $DB_HOST"
    echo "Database Name: $DB_NAME"
    echo "Database User: $DB_USER"
    echo

    echo "Proceed with installation? (y/n)"
    read -r confirm
    if [[ ! $confirm =~ ^[Yy]$ ]]; then
        print_status "Installation cancelled"
        exit 0
    fi

    echo
    print_status "Starting SimpleBlog installation..."

    # Create database and user if requested
    if [ "$CREATE_DB_USER" = "true" ]; then
        create_database
    fi

    # Import database schema
    import_database_schema

    # Create admin user
    create_admin_user

    # Configure files
    configure_files

    # Set file permissions
    if [ "$EUID" -eq 0 ]; then
        set_permissions
    else
        print_warning "Skipping file permissions (not running as root)"
        print_warning "You may need to set proper ownership and permissions manually"
    fi

    # Display summary
    display_summary
}

# Run main function
main "$@"