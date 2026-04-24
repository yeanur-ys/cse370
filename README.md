# Scentology (PHP + MySQL)

Course project starter for a dynamic multi-page website using **only PHP and MySQL**.

## Implemented now (Phase 1)
- Login / Sign Up
- Profile view + update
- Shops browse + register

## Project Structure
- `app/` core config, DB connection, auth helper
- `public/` website pages and assets
- `database/schema.sql` MySQL schema + demo seed

## Setup
1. Create database and tables:

```sql
SOURCE database/schema.sql;
```

Or run from terminal:

```powershell
mysql -u root -p < database/schema.sql
```

2. Update DB credentials in `app/config.php` if needed.

3. Start the app from project root:

```powershell
php -S localhost:8000 -t public
```

4. Open:
- `http://localhost:8000`

## Demo Account
- Email: `demo@scentology.com`
- Password hash is seeded only for structure; easiest is to create a new account from Sign Up page.

## Notes
- Uses `password_hash()` and `password_verify()`.
- `shops` supports city/filter and simple inventory notes.
- Map integration can be added later in next phase.
