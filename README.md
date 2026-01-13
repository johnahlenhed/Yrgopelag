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

## Code Review
- You have 2 index.php files, the root redirects to the public/index.php. This is an okay safety measure but its better to set the webroot to /public instead when possible. This solution isn't 100% secure and it can affect SEO and loadtimes.
- Public/index.php file is very large, try to seperate large blocks of code into several files. For example the javascript block should be its own file.
- Tiny tip, I see you type '<?php echo' a lot, '<?=' is the short version. It looks cleaner and is faster to write.
- Public/book.php:20-28 Are these inputs ever cleaned before they're used? Dont forget to use sanitization functions like trim() and htmlspecialchars()
- Public/book.php:52-54 This code makes it impossible to book a stay thats longer than 1 day, I see that your backend also only looks for arrival date to see if a room is booked or not. Datetime can be used to calculate days between two dates with the built in ->diff() function and a simple <= and >= works to check avaliabilty over multiple day bookings
- Public/book.php:180-189 You send the 'receipt' after taking payment and inserting the stay into your database but the receipt api endpoint checks if the guest is already booked into a diffrent hotel on the same day so it can throw a fatal error which should abort the booking. So it's possible that you've stolen money from guests and they never got the points for the stay
- styles.css could use some better names for elements, more descriptive. '.confirmation-container' 1 and 2 isn't saying very much. Also you're missing 'box-sizing: border-box', that makes it much easier to set the layout.
