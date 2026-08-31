# Project context
This is an old project that needs improvements. When working with this codebase, focus on what can be improved and explain why the suggested improvements are necessary.

## About this project
This is a school assignment, hotel booking website that connects to the Centralbank, set up by the class teacher. Written in plain PHP and SQL.

## Status
A security and correctness pass has already been done: API keys/transfer codes are no longer logged, the admin password is bcrypt-hashed instead of plaintext, CSRF protection and security headers are in place, session/auth is hardened (lockout, secure cookies), and non-public folders (`config/`, `src/`, `includes/`, `database/`) are blocked from direct web access.

The booking/payment flow (`public/book.php`) is rollback-safe: Centralbank has no refund/reversal endpoint, so a booking is only deleted while nothing has been charged yet; once money moves, the booking is kept and flagged for manual follow-up instead of silently lost. The most tangled logic from `public/book.php` and `public/admin/dashboard.php` was pulled into `src/BookingService.php` and `src/AdminDashboardService.php`; the rest of `public/` is intentionally left as thin page scripts rather than a full MVC rewrite.

## Known follow-ups
- Production (MySQL, one.com) still needs the schema migration applied manually: `status`/`transfer_code` columns plus a unique index on `bookings(room_type, arrival_date)`. See `database/schema.sql` for the SQLite version already applied locally.
- Bookings can end up with `status = 'payment_failed'` if a deposit fails after money already moved. There's no admin UI to see/resolve these yet — currently only visible via a direct DB query or the error log.
- The admin password hash in `.env` still corresponds to the original weak default password from before this pass; only its storage format was fixed (bcrypt hash instead of plaintext). Worth rotating to something stronger — regenerate with `php -r 'echo password_hash("your-new-password", PASSWORD_DEFAULT), PHP_EOL;'` and update `ADMIN_PASSWORD_HASH` in `.env`.

## Agents
- `security-improver` — scans for security risks and vulnerabilities specifically.
- `code-improver` — scans for general code quality issues (duplication, readability, refactors).

Run both for a broad review; reach for `security-improver` first when time is limited.
