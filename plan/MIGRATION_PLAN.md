# HCP Legacy to Laravel + Filament v4 Migration Plan

## Overview
This plan outlines the comprehensive migration from the legacy Bootstrap 4 PHP application in `hcp/` to a modern Laravel 12 + Filament v4 application, focusing on the three core database tables: `vmails`, `valias`, and `vhosts`, plus custom information pages.

## Phase 1: Foundation & Database Setup

### 1.1 Database Models Creation
Create Laravel Eloquent models with proper relationships and validation:

#### Vmail Model (`app/Models/Vmail.php`)
```php
- Fields: id, user, gid, uid, active, clearpw, password, home, created_at, updated_at
- Validation: email format for user field, password encryption
- Scopes: active mailboxes, domain filtering
- Accessors: formatted home path, password masking
- Mutators: password hashing, path normalization
```

#### Valias Model (`app/Models/Valias.php`)
```php
- Fields: id, source, target, active, created_at, updated_at
- Relationships: belongs to domain via source parsing
- Validation: email format, domain existence checks
- Complex logic: catchall handling, collision detection
- Scopes: active aliases, domain filtering
```

#### Vhost Model (`app/Models/Vhost.php`)
```php
- Fields: id, domain, status, created_at, updated_at
- Relationships: has many vmails, has many valias
- Validation: domain format, uniqueness
- Scopes: active domains, status filtering
```

### 1.2 Database Migrations
```bash
php artisan make:migration create_vmails_table
php artisan make:migration create_valias_table  
php artisan make:migration create_vhosts_table
```

### 1.3 Model Factories & Seeders
Create realistic test data generators for development and testing.

## Phase 2: Core CRUD Resources

### 2.1 Filament Resources

#### VmailResource (`app/Filament/Resources/VmailResource.php`)
**Features:**
- Table view with user, mailbox size, message count, status
- Create/Edit forms with password generation
- Password change functionality (modal)
- Bulk actions: activate/deactivate, delete
- Filters: domain, status, creation date
- Search: by user email
- Custom actions: password reset, mailbox stats

**Business Logic Migration:**
- Port password validation from `vmails.php:47`
- Implement system commands: `addvmail`, `delvmail`, `chpw`  
- Add password strength requirements
- Integrate with system user creation

#### ValiasResource (`app/Filament/Resources/ValiasResource.php`)
**Features:**
- Table view with source, target (comma-separated), domain, status
- Complex create/edit forms handling multiple sources/targets
- Advanced validation logic
- Bulk operations
- Domain-based filtering
- Alias conflict detection

**Business Logic Migration:**
- Port complex validation from `valias.php:25-183`
- Domain existence verification
- Catchall alias handling
- Source/target collision detection
- Email format validation with IDN support
- Multi-source/target processing

#### VhostResource (`app/Filament/Resources/VhostResource.php`)
**Features:**
- Table view with domain, alias count, mailbox count, quotas, status
- Create forms with system integration
- Quota management
- SSL/CMS configuration options
- Domain statistics
- System integration for vhost creation/deletion

**Business Logic Migration:**
- Port domain validation from `vhosts.php:40-67`
- System integration: `addvhost`, `delvhost` commands
- Quota validation and management
- Domain path existence checks
- SSL certificate management

### 2.2 Advanced Filament Features

#### Custom Table Columns
- Status indicators with color-coded badges
- Progress bars for quota usage
- Formatted file sizes and dates
- Clickable domains/emails with tooltips

#### Form Components
- Password generators with strength indicators
- Domain selectors with validation
- Quota sliders with visual feedback
- Multi-input fields for aliases

#### Actions & Bulk Actions
- Password reset with email notifications
- Domain DNS propagation checks
- Bulk status changes
- Export functionality

## Phase 3: Custom Information Pages

### 3.1 InfoSys Page (`app/Filament/Pages/InfoSys.php`)
**Features:**
- Real-time system metrics dashboard
- CPU, Memory, Disk usage with progress bars
- Load averages and system uptime
- OS information and kernel version
- Network information (IP, hostname)
- Auto-refresh capabilities

**Business Logic Migration:**
- Port system info gathering from `infosys.php:18-101`
- CPU usage calculation with 1-second sampling
- Memory usage parsing from `/proc/meminfo`
- Disk space calculations
- Load average display
- OS detection from `/etc/os-release`

### 3.2 InfoMail Page (`app/Filament/Pages/InfoMail.php`)
**Features:**
- Mail queue status display
- Postfix log summary
- Mail statistics and graphs
- Log file analysis
- Real-time updates

**Business Logic Migration:**
- Port mail queue display from `infomail.php:16`
- Postfix log parsing and summary
- Mail statistics calculation
- Log rotation and cleanup
- Queue management commands

### 3.3 Processes Page (`app/Filament/Pages/Processes.php`)
**Features:**
- System process listing
- Process filtering and search
- Resource usage monitoring
- Process management controls
- Real-time updates

**Business Logic Migration:**
- Port process listing from `processes.php:13`
- System command integration
- Process filtering and sorting
- Resource usage display

## Phase 4: Business Logic Services

### 4.1 Mail Management Service (`app/Services/MailService.php`)
```php
class MailService {
    public function createMailbox(string $user): bool
    public function deleteMailbox(string $user): bool  
    public function changePassword(string $user, string $password): bool
    public function getMailboxStats(string $user): array
    public function validateEmailDomain(string $email): bool
}
```

### 4.2 Alias Management Service (`app/Services/AliasService.php`)
```php
class AliasService {
    public function validateAlias(string $source, string $target): bool
    public function checkCollisions(string $source): array
    public function handleCatchall(string $domain): bool
    public function processMultipleTargets(array $targets): array
}
```

### 4.3 System Service (`app/Services/SystemService.php`)
```php
class SystemService {
    public function getSystemInfo(): array
    public function getMailQueueStatus(): array
    public function getProcessList(): array
    public function executeSystemCommand(string $command): array
}
```

## Phase 5: Advanced Features

### 5.1 Real-time Updates
- WebSocket integration for live updates
- Auto-refresh for system metrics
- Live mail queue monitoring
- Process status updates

### 5.2 Security Enhancements
- Role-based access control
- Audit logging for all actions
- API rate limiting
- CSRF protection
- Input sanitization

### 5.3 Performance Optimization
- Database query optimization
- Caching for system metrics
- Lazy loading for relationships
- Pagination for large datasets

## Phase 6: Testing Strategy

### 6.1 Unit Tests
- Model validation tests
- Service layer tests
- Business logic tests
- Command execution tests

### 6.2 Feature Tests
- CRUD operations
- Form validation
- Resource interactions
- Page functionality

### 6.3 Integration Tests
- System command integration
- Database transactions
- File system operations
- Email functionality

## Phase 7: Deployment & Migration

### 7.1 Data Migration Scripts
```bash
php artisan make:command MigrateVmails
php artisan make:command MigrateValias
php artisan make:command MigrateVhosts
```

### 7.2 Configuration Management
- Environment-specific configs
- Database connection setup
- System command paths
- Security settings

### 7.3 Backup Strategy
- Database backup procedures
- Configuration backup
- System state snapshots
- Rollback procedures

## Implementation Timeline

### Week 1-2: Foundation
- Database models and migrations
- Basic Filament resources
- Core business logic services

### Week 3-4: CRUD Implementation
- Complete resource functionality
- Advanced form components
- Validation logic migration

### Week 5-6: Custom Pages
- System information pages
- Real-time updates
- Dashboard integration

### Week 7-8: Testing & Polish
- Comprehensive testing
- Performance optimization
- Security hardening
- Documentation

## Key Migration Considerations

### 1. System Integration
- Preserve existing shell command integration
- Maintain compatibility with system scripts
- Handle file permissions properly

### 2. Data Integrity
- Validate all migrated data
- Preserve existing relationships
- Handle edge cases from legacy system

### 3. User Experience
- Modern, responsive interface
- Intuitive navigation
- Comprehensive error handling
- Helpful tooltips and documentation

### 4. Performance
- Efficient database queries
- Proper indexing strategy
- Caching for frequently accessed data
- Optimized system command execution

### 5. Security
- Secure password handling
- Proper input validation
- Protection against injection attacks
- Audit trail for all operations

This migration plan ensures a smooth transition from the legacy system while maintaining all existing functionality and adding modern features expected in a contemporary web application.