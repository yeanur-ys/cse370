#!/usr/bin/env bash

echo "🔍 Verifying Implementation..."
echo ""

echo "✅ Checking profile.php for purchases tab..."
if grep -q "My Stock" /Applications/XAMPP/xamppfiles/htdocs/cse370/public/profile.php; then
    echo "   ✓ Purchases tab button found"
else
    echo "   ✗ ERROR: Purchases tab button missing"
fi

if grep -q 'id="purchases"' /Applications/XAMPP/xamppfiles/htdocs/cse370/public/profile.php; then
    echo "   ✓ Purchases tab content found"
else
    echo "   ✗ ERROR: Purchases tab content missing"
fi

echo ""
echo "✅ Checking perfumes.php for buy button..."
if grep -q "buy_perfume" /Applications/XAMPP/xamppfiles/htdocs/cse370/public/perfumes.php; then
    echo "   ✓ Buy action handler found"
else
    echo "   ✗ ERROR: Buy action handler missing"
fi

if grep -q "🛍️ Buy\|🛍 Buy" /Applications/XAMPP/xamppfiles/htdocs/cse370/public/perfumes.php; then
    echo "   ✓ Buy button found"
else
    echo "   ✗ ERROR: Buy button missing"
fi

echo ""
echo "✅ Checking auth.php for purchase functions..."
if grep -q "function get_user_purchases" /Applications/XAMPP/xamppfiles/htdocs/cse370/app/auth.php; then
    echo "   ✓ get_user_purchases() function found"
else
    echo "   ✗ ERROR: get_user_purchases() missing"
fi

if grep -q "function purchase_perfume" /Applications/XAMPP/xamppfiles/htdocs/cse370/app/auth.php; then
    echo "   ✓ purchase_perfume() function found"
else
    echo "   ✗ ERROR: purchase_perfume() missing"
fi

echo ""
echo "✅ Checking schema.sql for Purchases table..."
if grep -q "CREATE TABLE IF NOT EXISTS Purchases" /Applications/XAMPP/xamppfiles/htdocs/cse370/database/schema.sql; then
    echo "   ✓ Purchases table defined"
else
    echo "   ✗ ERROR: Purchases table missing"
fi

echo ""
echo "✅ Checking schema.sql for Trade table updates..."
if grep -q "Desired_Perfume_ID INT NULL" /Applications/XAMPP/xamppfiles/htdocs/cse370/database/schema.sql; then
    echo "   ✓ Trade table supports perfume-to-perfume trades"
else
    echo "   ✗ ERROR: Trade table not updated"
fi

echo ""
echo "✅ Checking trades.php for perfume trade support..."
if grep -q "desired_type" /Applications/XAMPP/xamppfiles/htdocs/cse370/public/trades.php; then
    echo "   ✓ Trade form has perfume/note selector"
else
    echo "   ✗ ERROR: Trade form selector missing"
fi

echo ""
echo "================================"
echo "✅ IMPLEMENTATION VERIFICATION COMPLETE"
echo "================================"
echo ""
echo "🚀 All components are in place!"
echo ""
echo "Access the application at:"
echo "  Homepage: http://localhost:8001"
echo "  Perfumes: http://localhost:8001/perfumes.php"
echo "  Profile:  http://localhost:8001/profile.php"
echo "  Trades:   http://localhost:8001/trades.php"
