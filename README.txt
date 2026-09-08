- index.php: storefront
- login.php/register.php/logout.php: authentication
- function.php: shared business functions
- validation.php: validation helpers
- api.php: AJAX backend endpoint used by cart/checkout JavaScript
- success.php: successful checkout page
- receipt.php: printable order receipt
- admin.php: product, stock, restock and order management
- upload/products/: product image upload storage
- db.sql: MySQL schema and demo data

Run with XAMPP:
1. Copy fundador_full to C:/xampp/htdocs/
2. Start Apache and MySQL.
3. Import db.sql in phpMyAdmin.
4. Open http://localhost/fundador_full/
5. Admin: admin@fundador.local / admin123
