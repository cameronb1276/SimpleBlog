# SimpleBlog - Modern Responsive Blogging Platform

![SimpleBlog](https://img.shields.io/badge/SimpleBlog-v2.0-blue.svg) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-green.svg) ![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg) ![License](https://img.shields.io/badge/License-MIT-yellow.svg)

A beautiful, modern, and fully responsive blogging platform built with PHP and MySQL. Features a powerful theme system, mobile-first design, and enterprise-level security.

## ✨ Features

### 🎨 **Beautiful Themes**
- **Default Theme**: Clean, professional light theme
- **Dark Theme**: Modern dark interface
- **Mobile-First**: Optimized for all devices
- **Custom Themes**: Easy theme development system

### 📱 **Mobile-Optimized**
- Responsive design for mobile, tablet, and desktop
- Touch-friendly navigation
- Optimized performance
- Progressive enhancement

### 🔒 **Enterprise Security**
- SQL injection protection
- CSRF protection
- Secure session management
- File upload security
- Rate limiting
- Input sanitization

### ⚡ **Performance**
- Optimized database queries
- Efficient caching
- Compressed assets
- Fast loading times

### 👨‍💼 **Admin Features**
- Intuitive admin dashboard
- Post management (create, edit, delete)
- Theme management system
- User management
- Settings configuration

## 🚀 Quick Start

### Option 1: Automatic Installation (Recommended)

1. **Download and extract** SimpleBlog to your web server
2. **Run the setup script**:
   ```bash
   sudo bash setup.sh
   ```
3. **Follow the interactive prompts** for configuration
4. **Access your blog** at your domain
5. **Login to admin** at `/admin/` with your credentials

### Option 2: Manual Installation

See the detailed installation instructions in the script comments for step-by-step manual installation.

## 📋 System Requirements

- **PHP 7.4+** (PHP 8.0+ recommended)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache 2.4+** or **Nginx 1.18+**
- **PHP Extensions**: pdo_mysql, gd/imagick, fileinfo, openssl, mbstring

## 🎨 Theme System

SimpleBlog includes a powerful theme system:

### Included Themes
- **Default**: Professional light theme
- **Dark**: Modern dark theme

### Theme Structure
```
themes/
├── default/
│   ├── header.php
│   ├── footer.php
│   ├── index.php
│   ├── post.php
│   ├── style.css
│   └── theme.json
└── dark/
    ├── header.php
    ├── footer.php
    ├── index.php
    ├── post.php
    ├── style.css
    └── theme.json
```

### Creating Custom Themes
1. Create a new directory in `/themes/`
2. Include all required files (header.php, footer.php, index.php, post.php, style.css)
3. Add optional theme.json for metadata
4. Upload via admin panel or place directly in themes folder

## 🔒 Security Features

SimpleBlog implements multiple security layers:

### Input Security
- **SQL Injection Protection**: All database queries use prepared statements
- **Cross-Site Scripting (XSS) Protection**: All output is properly escaped
- **Cross-Site Request Forgery (CSRF) Protection**: CSRF tokens on all forms
- **Input Sanitization**: All user input is sanitized and validated

### Session Security
- **Secure Session Configuration**: HTTPOnly, Secure, SameSite cookies
- **Session Regeneration**: Regular session ID regeneration
- **Session Timeout**: Automatic logout after inactivity
- **Brute Force Protection**: Rate limiting on login attempts

### File Security
- **Upload Validation**: Strict file type and size validation
- **Malicious Content Detection**: Basic content scanning
- **Path Traversal Protection**: Prevents directory traversal attacks
- **Secure File Permissions**: Proper file and directory permissions

## 📱 Mobile Features

### Responsive Design
- **Mobile-First CSS**: Designed for mobile devices first
- **Flexible Grid System**: Adapts to any screen size
- **Touch-Friendly**: Large tap targets and intuitive gestures
- **Fast Loading**: Optimized for mobile networks

### Progressive Enhancement
- **Core Functionality**: Works without JavaScript
- **Enhanced Experience**: JavaScript improves user experience
- **Offline Fallbacks**: Graceful degradation when features unavailable

## 🎯 Browser Support

SimpleBlog supports all modern browsers:
- **Chrome 70+**
- **Firefox 65+**
- **Safari 12+**
- **Edge 79+**
- **Mobile browsers** (iOS Safari, Chrome Mobile, Samsung Internet)

## 📊 Performance

### Optimization Features
- **Efficient Database Queries**: Optimized with proper indexing
- **CSS/JS Optimization**: Minified and compressed assets
- **Image Optimization**: Proper image sizing and lazy loading
- **Caching Headers**: Browser caching for static assets

## 🛠️ Development

### File Structure
```
simpleblog/
├── admin/               # Admin panel files
│   ├── dashboard.php
│   ├── posts.php
│   ├── themes.php
│   └── ...
├── assets/             # Static assets
│   ├── css/
│   ├── js/
│   └── uploads/
├── includes/           # Core functionality
│   ├── config.php
│   └── functions.php
├── themes/             # Theme files
│   ├── default/
│   └── dark/
├── index.php           # Main entry point
├── post.php           # Single post display
└── setup.sh           # Installation script
```

## 📝 License

SimpleBlog is released under the MIT License.

## 🤝 Contributing

We welcome contributions! Please feel free to:
- Report bugs
- Suggest new features
- Submit pull requests
- Improve documentation

## 📞 Support

### Getting Help
1. **Read the documentation**: Check the setup script for installation help
2. **Check server logs**: Review error logs for specific issues
3. **Verify requirements**: Ensure all system requirements are met

## 🎉 Getting Started

1. **Download** SimpleBlog
2. **Run** `bash setup.sh` for automatic installation
3. **Access** your new blog
4. **Create** your first post
5. **Customize** with themes and settings

**Happy blogging!** 🚀

---

*SimpleBlog - Built with ❤️ for the modern web*

**Created by Cam**
