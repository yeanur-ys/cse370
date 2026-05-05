# Quick Reference - New Features

## 🎯 User-Facing Features

### 1. My Collection (Profile → Collection Tab)
**What it does:** Users can track perfumes they own
- Add perfumes with optional purchase date and notes
- View collection with images, prices, and details
- Remove items from collection

**Location:** Profile page → "📚 My Collection" tab  
**Added via:** Perfume detail page or collection management

### 2. My Reviews (Profile → Reviews Tab) 
**What it does:** View all reviews you've written
- See perfume, rating, and comment
- Sorted by most recent first

**Location:** Profile page → "⭐ My Reviews" tab

### 3. Enhanced Wishlist
**What it does:** Better wishlist display
- Shows perfume images
- Shows prices
- Cleaner card layout

**Location:** Profile page → "❤️ My Wishlist" tab

### 4. Cancel My Listings
**What it does:** Sellers can cancel their own listings
- Click "✕ Cancel Listing" button on your own listings
- Status changes to "Cancelled"
- Only available for pending listings

**Location:** Listings page  
**Status filter:** Use tabs to see Available, Sold, Cancelled, or All

### 5. Cancel My Trades
**What it does:** Traders can cancel pending trades
- Click "✕ Cancel Trade" button on your own pending trades
- Status changes to "Cancelled"
- Others can still accept pending trades

**Location:** Trades page  
**Status indicator:** Shows "Pending", "Accepted", or "Cancelled"

---

## 🔧 Developer Reference

### New Database Table
**Collection** - Maps users to their perfumes
- `User_ID` (foreign key)
- `Perfume_ID` (foreign key)
- `Purchase_Date` (optional)
- `Notes` (optional, 255 chars max)
- `Added_At` (timestamp, auto-set)

### New Helper Functions (app/auth.php)

```php
get_user_collection(int $userId): array
is_in_collection(int $userId, int $perfumeId): bool
add_to_collection(int $userId, int $perfumeId, ?string $purchaseDate, string $notes = ''): void
remove_from_collection(int $userId, int $perfumeId): void
get_user_reviews(int $userId): array
```

### Modified Pages

| Page | Changes |
|------|---------|
| perfume-detail.php | Collection add/remove buttons and form |
| profile.php | 4 tabs: Profile, Collection, Wishlist, Reviews |
| wishlist.php | Added images, prices |
| listings.php | Cancel button, status filters |
| trades.php | Cancel button for own trades, improved form labels |

---

## 📋 User Workflows

### Adding a Perfume to My Collection
1. Go to any perfume detail page
2. Scroll to "Add to My Collection" section
3. (Optional) Enter purchase date
4. (Optional) Add notes (e.g., "Gift", "Limited Edition")
5. Click "✓ Add to Collection"
6. View in Profile → Collection tab

### Viewing My Collection
1. Click Profile → "📚 My Collection"
2. See all owned perfumes with images and details
3. Click perfume name to go to detail page

### Cancelling a Listing (Sellers)
1. Go to Listings page
2. Find your listing (marked as "Available")
3. Click "✕ Cancel Listing" button
4. Listing status changes to "Cancelled"

### Cancelling a Trade
1. Go to Trades page
2. Find your pending trade
3. Click "✕ Cancel Trade" button
4. Trade status changes to "Cancelled"

---

## 🔐 Permission Model

### Collection
- ✅ Can view own collection
- ✅ Can add/remove own perfumes
- ❌ Cannot view others' collections

### Listings
- ✅ Can cancel own available listings
- ❌ Cannot cancel others' listings
- ❌ Cannot cancel sold listings
- ✅ Anyone can purchase available listings

### Trades
- ✅ Can cancel own pending trades
- ❌ Cannot cancel others' trades
- ✅ Can accept others' pending trades
- ❌ Cannot modify accepted trades

---

## 🐛 Troubleshooting

**Collection table not created?**
- Run `http://localhost:8001/init-db.php` again
- Check database error messages

**Images not showing in wishlist?**
- Ensure perfume images exist in `/public/assets/images/`
- Check asset_image_url() function is working

**Can't cancel listing/trade?**
- Ensure you're logged in
- Ensure it's your own listing/trade
- Ensure status is still "Available"/"Pending" (not yet completed)

---

## 📊 Status Values

**Listings:**
- `Available` (default) - Can be purchased or cancelled
- `Sold` - Purchased by another user
- `Cancelled` - Seller cancelled the listing

**Trades:**
- `Pending` (default) - Waiting for acceptance, can be cancelled by creator
- `Accepted` - Another user accepted the trade
- `Cancelled` - Trade creator cancelled
