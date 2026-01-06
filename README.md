# Yrgopelag

# Borta bra, hemma bäst

A hotel booking system for the fictional island of New Sweden, built as part of the Yrgopelag school assignment.

Live Site: https://yrgopelag.johnahlenhed.se/

### Features
Four-Star Hotel

- Graphical Room Availability Calendar - Visual calendar showing available dates for all three rooms
- TransferCode Service - Guests can create transfer codes directly through the hotel (no need to visit Central Bank separately)
- Loyalty Discount - Returning customers receive automatic discount (configurable via admin panel)
- Admin Dashboard - Secure admin panel for managing prices, star rating, discounts, and features

## Tech stack

Backend:
- PHP 8.3 - Server-side logic with strict typing
- SQLite - Local development database
- MySQL/MariaDB - Production database (one.com)
- Composer - Dependency management (local development)

Frontend:
- HTML5 - Semantic markup
- CSS3 - Custom styling with responsive design
- JavaScript (Vanilla) - Interactive calendar and price calculator

APIs & Services:
- Yrgopelag Central Bank API - Payment processing and receipts
- cURL - HTTP client for API requests

### Installation

Prerequisites:
- PHP 8.0 or higher
- Composer
- SQLite (for local development)

## Important Notes
- Production uses MySQL, local uses SQLite
- config.php automatically detects database type via DB_DRIVER
- The key column in settings table uses backticks for MySQL compatibility

## Database Schema
The application uses 5 tables:

- rooms - Three room types with prices
- bookings - Guest reservations
- features - Available activities/amenities
- booking_features - Junction table linking bookings to features
- settings - Application settings (star rating, discounts)

See schema_mysql.sql for complete structure.

## Admin Panel
Access: /public/admin/

Features:

- Update hotel star rating (1-5 stars)
- Modify room prices (Economy, Standard, Luxury)
- Configure loyalty discount percentage
- Enable/disable features and set prices
- Fetch and sync features from Central Bank

Default admin password: Set via ADMIN_PASSWORD in .env

## Central Bank Integration
The hotel integrates with the Yrgopelag Central Bank API:

- Payment Processing - Validates and deposits transfer codes
- Receipt Submission - Sends booking details for tourist points
- Feature Syncing - Fetches active features from Central Bank
- TransferCode Service - Creates transfer codes on behalf of guests

### Credits
- Developer: John Ahlenhed
- School: Yrgo - Webbutvecklare 2025
- Course: Programmering & Datakällor
- Assignment: Yrgopelag
- Central Bank: https://www.yrgopelag.se/centralbank/