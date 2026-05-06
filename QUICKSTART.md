# 🚀 Scentology - Quick Start Guide

Welcome to **Scentology**, a premium perfume marketplace and community hub where users can browse, buy, trade, and review perfumes.

This guide will walk you through setting up the project locally on **Windows, macOS, and Linux** using port `8000`.

---

## 🛠️ Prerequisites

Before you begin, ensure you have the following installed on your system:
- **PHP** (v8.0 or newer)
- **MySQL** (v8.0 or newer) or MariaDB
- **XAMPP / MAMP / WAMP** (Optional, but recommended for beginners)

---

## ⚙️ Step 1: Database Setup

Scentology uses a single initialization file to automatically set up the database, tables, and sample data.

### 1. Start your MySQL Server
- **Windows**: Open XAMPP Control Panel and start **MySQL**.
- **macOS**: Open XAMPP/MAMP Control Panel and start **MySQL**, or run `brew services start mysql` if using Homebrew.
- **Linux**: Run `sudo systemctl start mysql` or `sudo service mysql start`.

### 2. Configure Database Credentials
By default, the application looks for root access without a password. Open `app/config.php` and verify these settings match your local MySQL server:
```php
define('DB_HOST', '127.0.0.1'); // Ensure this is your DB host
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', ''); // Add your mysql password if you have one
define('DB_NAME', 'scentology');
```

---

## 🌍 Step 2: Start the Local Server (Port 8000)

We will use PHP's built-in development server to run the application on port `8000`. 

Open your terminal or command prompt, navigate to the root directory of the `cse370` project, and run the following command:

### For Windows:
```cmd
cd path\to\your\folder\cse370
php -S localhost:8000 -t public
```

### For macOS / Linux:
```bash
cd /path/to/your/folder/cse370
php -S localhost:8000 -t public
```

*Note: The `-t public` flag ensures the server treats the `public` folder as the document root, which is required for security and routing.*

---

## 🚀 Step 3: Initialize the Project

Now that your server and database are running:

1. Open your web browser and go to:
   **👉 http://localhost:8000/init-db.php**
   
2. This script will automatically:
   - Create the `scentology` database.
   - Set up all required tables (Users, Perfumes, Wishlist, Trades, etc.).
   - Insert sample perfumes, brands, notes, and a test user.

3. Wait for the green **"Database initialized successfully!"** message.

---

## 🎉 Step 4: Explore Scentology!

Once initialized, navigate to the homepage:
**👉 http://localhost:8000/index.php**

### 🧪 Test User Account
You can log in to explore user-only features (Wishlist, Trades, Stock tracking):
- **Email**: `demo@scentology.com`
- **Password**: `password`

### 🌟 Available Features
- **Public**: Browse the Perfume Catalog, Brands, and Shops. View Reviews.
- **Users**: Add perfumes to your Wishlist, write Reviews, and track your Stock purchases.
- **Trading Platform**: Request to trade a perfume you own for another perfume or note!
- **Sellers**: Register shops, manage physical stock, and post community Listings.

Happy scent hunting! 🧴✨
