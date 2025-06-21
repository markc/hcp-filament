# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This project is a Laravel 12 + Filament v4 application that replaces an old Bootstrap 4 PHP application (located in the `hcp/` folder). The goal is to create a modern admin panel with CRUD operations for three main database tables: `valias`, `vhosts`, and `vmails`.

## Database Schema

The core database tables are defined in the `plan/` folder:

- **valias** (`plan/valias.sql`): Email alias management
  - Fields: `id`, `source`, `target`, `active`, `created_at`, `updated_at`
  - Manages email forwarding/aliases with source→target mapping

- **vhosts** (`plan/vhosts.sql`): Virtual host management  
  - Fields: `id`, `domain`, `status`, `created_at`, `updated_at`
  - Manages domain configurations with unique domain constraint

- **vmails** (`plan/vmails.sql`): Virtual mailbox management
  - Fields: `id`, `user`, `gid`, `uid`, `active`, `clearpw`, `password`, `home`, `created_at`, `updated_at`
  - Manages email accounts with authentication

## Development Commands

### Laravel/Artisan Commands
```bash
# Development server with hot reload
composer dev

# Run tests
composer test
# OR 
php artisan test

# Code formatting
./vendor/bin/pint

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Frontend Development
```bash
# Development build with hot reload
npm run dev

# Production build
npm run build
```

## Architecture

### Filament Admin Panel
- Admin panel configured in `app/Providers/Filament/AdminPanelProvider.php`
- Resources auto-discovered from `app/Filament/Resources/`
- Admin panel accessible at `/admin` with authentication required
- Uses Amber as primary color theme

### Laravel Structure
- **Models**: Place in `app/Models/` (e.g., `Valias.php`, `Vhost.php`, `Vmail.php`)
- **Filament Resources**: Place in `app/Filament/Resources/` (e.g., `ValiasResource.php`)
- **Migrations**: Use `database/migrations/` for database schema changes
- **Testing**: Pest PHP testing framework configured

### Database
- Default connection uses SQLite (`database/database.sqlite`)
- Test environment uses in-memory SQLite
- Original MySQL schema provided in `plan/*.sql` files

## Legacy Code Reference

The `hcp/` folder contains the original PHP application for reference:
- **valias.php**: Email alias CRUD with complex validation logic
- **vhosts.php**: Domain management with system integration
- **vmails.php**: Mailbox management with password handling

Key business logic to preserve:
- Email validation and domain checking
- Alias collision detection (source vs existing mailboxes)
- Catchall alias handling
- Password encryption for vmails

## Filament Resource Patterns

When creating Filament resources:
1. Use `php artisan make:filament-resource ModelName` to generate resources
2. Configure table columns, forms, and actions in the resource class
3. Add navigation labels and icons in the resource
4. Use Filament's built-in form components for consistent UI

## Testing

- Pest PHP framework configured
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Database tests use in-memory SQLite
- Run with `composer test` or `php artisan test`