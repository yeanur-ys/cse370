# Scentology (PHP + MySQL)

Course project for a dynamic multi-page website using **only PHP and MySQL**. The database is strictly modeled after the provided EER diagram.

## Implemented Features
- **User Roles:** General Users and Sellers
- **Authentication:** Login / Sign Up with automatic role assignment
- **Profile Management:** 
  - General Users can manage a "Collection" of perfumes.
  - Sellers track their total sales.
- **Shops:** Browse all shops. Only authenticated Sellers can register new shops.
- **Listings:** Sellers can post item listings (Item Name, Price, Quantity, Condition).
- **Trades:** General users and Sellers can post what they are offering vs. what they desire.

## Project Structure
- `app/` core config, DB connection, auth helper
- `public/` frontend UI pages (`shops.php`, `listings.php`, `trades.php`, etc)
- `database/schema.sql` Complete MySQL schema aligning with EER definitions

## Setup
1. Create database and tables the Windows way (XAMPP):

```powershell
Get-Content database\schema.sql | C:\xampp\mysql\bin\mysql.exe -u root
```

2. Update DB credentials in `app/config.php` if needed.

3. Start the app from project root:

```powershell
C:\xampp\php\php.exe -S localhost:8000 -t public
```

4. Open:
- `http://localhost:8000`

## Architecture Notes
- The database uses table extension/inheritance `User` -> `Profile` -> `Seller`.
- Uses strictly native SQL with PHP PDO to avoid abstraction libraries.
