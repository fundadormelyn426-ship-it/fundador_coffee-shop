1. Copy/extract this project into C:\xampp\htdocs\
2. Open: http://localhost/fundador_full/
3. Start Apache and MySQL in XAMPP.
4. Database: fundador_coffee
5. db.sql uses CREATE DATABASE IF NOT EXISTS and CREATE TABLE IF NOT EXISTS.
   Do NOT DROP the database if you want to preserve existing orders.
6. Existing order history is stored in orders and order_items.
7. The duplicate js/script.js was removed; the root script.js is the active script.
8. Social buttons now open their configured public home pages in a new tab.
   Replace the data-url values in index.php with your actual pages if desired.

IMPORTANT:
- Back up your existing MySQL database before any schema changes.
- This build does not delete orders.
