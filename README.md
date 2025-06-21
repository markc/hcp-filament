# HCP Filament - Mail Management System

[![Main CI/CD Pipeline](https://github.com/markc/hcp-filament/actions/workflows/main.yml/badge.svg)](https://github.com/markc/hcp-filament/actions/workflows/main.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-4.x-orange.svg)](https://filamentphp.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A modern, comprehensive mail management system built with Laravel and Filament for hosting control panels. HCP Filament provides an intuitive interface for managing virtual hosts, email accounts, aliases, and mail-related configurations.

## ✨ Features

### 🏠 **Virtual Host Management**
- Domain registration and configuration
- Active/inactive status toggle
- Domain-based filtering and search
- Automatic mail account relationship tracking

### 📧 **Email Account Management** 
- Full mailbox lifecycle management
- Clear password storage with automatic hashing
- Unix-style UID/GID support for mail storage
- Home directory path configuration
- Domain-based account filtering

### 🔄 **Email Alias System**
- Source-to-target email forwarding
- Multi-target alias support
- Catchall alias configuration (@domain.com)
- Inline editing for quick updates

### 👥 **User Management**
- Role-based access control (Admin, Agent, Customer)
- User activation/deactivation
- Comprehensive user filtering

### 🎨 **Modern UI/UX**
- **Modal-based CRUD operations** - All create/edit actions in modals
- **Inline table editing** - Direct editing of key fields without page navigation
- **Dynamic column management** - Reorderable columns with auto-apply visibility
- **Transparent editing interface** - Seamless inline editing experience
- **Smart table features** - Default sorting, comprehensive filtering, search
- **Responsive design** - Mobile-friendly interface

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Composer
- Node.js 20+
- SQLite/MySQL/PostgreSQL

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/markc/hcp-filament.git
   cd hcp-filament
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   touch database/database.sqlite  # For SQLite
   php artisan migrate --seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000/admin` to access the Filament admin panel.

### Default Login
- **Email**: `admin@example.com`
- **Password**: `password123`

## 🏗️ Architecture

### Database Schema

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│    users    │    │   vhosts    │    │   vmails    │    │   valias    │
├─────────────┤    ├─────────────┤    ├─────────────┤    ├─────────────┤
│ id          │    │ id          │    │ id          │    │ id          │
│ name        │    │ domain      │    │ user        │    │ source      │
│ email       │    │ active      │    │ password    │    │ target      │
│ role        │    │ created_at  │    │ clearpw     │    │ active      │
│ active      │    │ updated_at  │    │ uid         │    │ created_at  │
│ created_at  │    └─────────────┘    │ gid         │    │ updated_at  │
│ updated_at  │                       │ home        │    └─────────────┘
└─────────────┘                       │ active      │
                                      │ created_at  │
                                      │ updated_at  │
                                      └─────────────┘
```

### Technology Stack

- **Backend**: Laravel 12.x with PHP 8.4
- **Admin Panel**: Filament 4.x
- **Frontend**: Vite + NPM
- **Database**: SQLite (default), MySQL, PostgreSQL
- **Testing**: Pest PHP
- **Code Quality**: Laravel Pint
- **CI/CD**: GitHub Actions

## 🧪 Development

### Running Tests

```bash
# Run all tests
vendor/bin/pest

# Run with coverage
vendor/bin/pest --coverage

# Run specific test suite
vendor/bin/pest --testsuite=Unit
```

### Code Quality

```bash
# Check code style
vendor/bin/pint --test

# Fix code style
vendor/bin/pint

# Run security audit
composer audit
```

### Database Operations

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_new_table

# Create factory
php artisan make:factory ModelFactory
```

## 📦 Production Deployment

### Optimization Commands

```bash
# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Filament
php artisan filament:optimize

# Clear all caches
php artisan optimize:clear
```

### Environment Configuration

Key environment variables for production:

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

## 🔧 Configuration

### Mail Storage Configuration

The system supports Unix-style mail storage with configurable:

- **UID/GID**: User and group IDs for mail files
- **Home directories**: Customizable mail storage paths
- **Domain-based organization**: Automatic mail sorting by domain

### Filament Customization

Key Filament features configured:

- Collapsible sidebar on desktop
- Default table sorting by `updated_at`
- Modal-based create/edit operations
- Inline editing for frequently updated fields
- Dynamic column management

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Use Laravel Pint for code formatting

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework for web artisans
- [Filament](https://filamentphp.com) - Modern admin panel for Laravel
- [Pest PHP](https://pestphp.com) - Elegant testing framework
- [Laravel Pint](https://laravel.com/docs/pint) - Code style fixer

## 📞 Support

For support, please open an issue on GitHub or contact the maintainers.

---

**Built with ❤️ using Laravel & Filament**