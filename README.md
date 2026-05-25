# PRC Queue Kiosk System

This is a queue management system for the Professional Regulation Commission (PRC) Kiosk.

## Features

- **Service Management**: Define services and their prefixes.
- **Counter Management**: Manage physical counters/windows.
- **Queue Ticket System**: Issue, track, and manage queue tickets.
- **Authentication**: Secure login for staff and administrators.

## Tech Stack

- **Framework**: Laravel 12
- **Database**: MySQL
- **Queue Driver**: Redis (via Predis) or Database

## Installation

1.  Clone the repository.
2.  Run `composer install`.
3.  Copy `.env.example` to `.env` and configure your database.
4.  Run `php artisan key:generate`.
5.  Run `php artisan migrate`.
6.  Run `npm install && npm run build`.
7.  Serve the application: `php artisan serve`.

## License

Private / Proprietary
