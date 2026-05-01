# 🚀 Scentology - Quick Start Guide

## Your server is running on: **http://localhost:8000**

### ⚡ Quick Setup (2 Steps)

1. **Initialize Database**
   - Open: http://localhost:8000/init-db.php
   - This will create all tables and insert perfume data
   - Wait for "Database initialized successfully!" message

2. **View Your Website**
   - Homepage: http://localhost:8000/
   - Perfumes: http://localhost:8000/perfumes.php
   - Brands: http://localhost:8000/brands.php

---

## 📋 Pages Available

### Public Pages (No Login Required)
- **Home** - http://localhost:8000/index.php
- **Perfumes Catalog** - http://localhost:8000/perfumes.php
- **Brands** - http://localhost:8000/brands.php
- **Shops** - http://localhost:8000/shops.php
- **Login** - http://localhost:8000/login.php
- **Sign Up** - http://localhost:8000/signup.php

### Logged-In User Pages
- **Profile** - http://localhost:8000/profile.php
- **Wishlist** - http://localhost:8000/wishlist.php
- **Reviews** - http://localhost:8000/reviews.php
- **Listings** - http://localhost:8000/listings.php
- **Trades** - http://localhost:8000/trades.php

### Admin/Setup Pages
- **Database Setup** - http://localhost:8000/init-db.php

---

## 🔧 Troubleshooting

### If you get database errors:
1. Go to http://localhost:8000/init-db.php to initialize database
2. Wait for "Database initialized successfully!" message
3. Then view http://localhost:8000/perfumes.php

### If MySQL isn't running:
1. Make sure MySQL is running (check XAMPP Control Panel for mysqld)
2. Check app/config.php for correct database credentials (default: root/empty password)

---

## 📊 Database Status

After running init-db.php, you should have:
- **15 Brands** (Creed, Dior, Tom Ford, etc.)
- **24 Perfumes** (complete with prices and notes)
- **Sample Users** (demo@scentology.com)

Test User: 
- Email: demo@scentology.com
- Password: password

---

## 🎯 Features Implemented

✅ Product Catalog with Images
✅ Brand Filtering
✅ Wishlist (login required)
✅ Reviews & Ratings
✅ User Profiles
✅ Shopping Wishlist
✅ Responsive Design
✅ Dark-themed Navigation

---

## 📝 Notes

- Server runs on localhost:8000 using PHP development server
- MySQL must be running
- All images are loaded from GitHub repository
- Database automatically creates on first setup
