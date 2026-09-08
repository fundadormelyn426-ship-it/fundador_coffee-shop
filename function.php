<?php

require_once __DIR__ . '/validation.php';

/*
|--------------------------------------------------------------------------
| CART FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Get current cart items, total, and item count.
 */
function cart_items(PDO $pdo)
{
    $cart  = $_SESSION['cart'] ?? [];
    $items = [];
    $total = 0;
    $count = 0;

    foreach ($cart as $id => $qty) {

        $stmt = $pdo->prepare(
            'SELECT id, name, price, image, stock
             FROM products
             WHERE id = ? AND is_active = 1'
        );

        $stmt->execute([(int) $id]);

        $product = $stmt->fetch();

        // Product no longer exists or has no stock
        if (
            !$product ||
            (int) $product['stock'] <= 0
        ) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        // Prevent quantity from exceeding available stock
        $qty = min(
            (int) $qty,
            (int) $product['stock']
        );

        // Remove invalid quantities
        if ($qty < 1) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        $_SESSION['cart'][$id] = $qty;

        $subtotal =
            (float) $product['price'] * $qty;

        $total += $subtotal;
        $count += $qty;

        $items[] = [
            'id'       => (int) $product['id'],
            'name'     => $product['name'],
            'price'    => (float) $product['price'],
            'image'    => $product['image'],
            'stock'    => (int) $product['stock'],
            'qty'      => $qty,
            'subtotal' => $subtotal
        ];
    }

    return [
        'items' => $items,
        'total' => $total,
        'count' => $count
    ];
}


/*
|--------------------------------------------------------------------------
| STOCK FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Add stock to a product.
 */
function add_stock(PDO $pdo, $productId, $quantity)
{
    $quantity = (int) $quantity;

    // Validate restock quantity
    if (
        $quantity < 1 ||
        $quantity > 10000
    ) {
        throw new Exception(
            'Restock quantity must be between 1 and 10,000.'
        );
    }

    // Update stock
    $stmt = $pdo->prepare(
        'UPDATE products
         SET stock = stock + ?
         WHERE id = ?'
    );

    $stmt->execute([
        $quantity,
        (int) $productId
    ]);

    // Product does not exist
    if (!$stmt->rowCount()) {
        throw new Exception(
            'Product not found.'
        );
    }

    // Get updated stock
    $stmt = $pdo->prepare(
        'SELECT name, stock
         FROM products
         WHERE id = ?'
    );

    $stmt->execute([
        (int) $productId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| ORDER FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Create a new customer order.
 */
function create_order(PDO $pdo, $name, $email)
{
    // Validate customer name
    if (!valid_name($name)) {
        throw new Exception(
            'Please enter a valid name.'
        );
    }

    // Validate customer email
    if (!valid_email($email)) {
        throw new Exception(
            'Please enter a valid email.'
        );
    }

    // Make sure cart is not empty
    if (empty($_SESSION['cart'])) {
        throw new Exception(
            'Your cart is empty.'
        );
    }

    // Start database transaction
    $pdo->beginTransaction();

    try {

        $locked = [];
        $total  = 0;

        /*
        |--------------------------------------------------------------------------
        | Check and lock products
        |--------------------------------------------------------------------------
        */

        foreach ($_SESSION['cart'] as $id => $qty) {

            $stmt = $pdo->prepare(
                'SELECT id, name, price, stock
                 FROM products
                 WHERE id = ?
                   AND is_active = 1
                 FOR UPDATE'
            );

            $stmt->execute([
                (int) $id
            ]);

            $product = $stmt->fetch();

            $qty = (int) $qty;

            // Product unavailable
            if (!$product) {
                throw new Exception(
                    'A product is no longer available.'
                );
            }

            // Not enough stock
            if (
                (int) $product['stock'] < $qty
            ) {
                throw new Exception(
                    'Not enough stock for ' .
                    $product['name'] .
                    '. Only ' .
                    $product['stock'] .
                    ' left.'
                );
            }

            // Calculate subtotal
            $subtotal =
                (float) $product['price'] * $qty;

            $total += $subtotal;

            $locked[] = [
                'id'       => $product['id'],
                'name'     => $product['name'],
                'price'    => $product['price'],
                'qty'      => $qty,
                'subtotal' => $subtotal
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Create Order
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            'INSERT INTO orders
                (user_id, customer_name, customer_email, total)
             VALUES
                (?, ?, ?, ?)'
        );

        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $name,
            $email,
            $total
        ]);

        $orderId = $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | Prepare Order Item & Stock Queries
        |--------------------------------------------------------------------------
        */

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                )
             VALUES
                (?, ?, ?, ?, ?, ?)'
        );

        $stockStmt = $pdo->prepare(
            'UPDATE products
             SET stock = stock - ?
             WHERE id = ?'
        );


        /*
        |--------------------------------------------------------------------------
        | Save Order Items & Deduct Stock
        |--------------------------------------------------------------------------
        */

        foreach ($locked as $item) {

            // Save order item
            $itemStmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['qty'],
                $item['subtotal']
            ]);

            // Deduct product stock
            $stockStmt->execute([
                $item['qty'],
                $item['id']
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Finish Transaction
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        // Clear cart after successful order
        $_SESSION['cart'] = [];

        return $orderId;

    } catch (Throwable $e) {

        // Roll back if something goes wrong
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

/**
 * Save an uploaded product image.
 */
function save_uploaded_product_image($file)
{
    // No image uploaded
    if (
        empty($file) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    // Upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception(
            'Image upload failed.'
        );
    }

    // Maximum file size: 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception(
            'Image must be 5MB or smaller.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Check MIME Type
    |--------------------------------------------------------------------------
    */

    $mime = (
        new finfo(FILEINFO_MIME_TYPE)
    )->file(
        $file['tmp_name']
    );

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new Exception(
            'Only JPG, PNG, and WebP images are allowed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Safe Filename
    |--------------------------------------------------------------------------
    */

    $filename =
        bin2hex(random_bytes(8)) .
        '.' .
        $allowed[$mime];


    /*
    |--------------------------------------------------------------------------
    | Create Upload Directory
    |--------------------------------------------------------------------------
    */

    $directory =
        __DIR__ . '/upload/products';

    if (!is_dir($directory)) {
        mkdir(
            $directory,
            0755,
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Move Uploaded File
    |--------------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $directory . '/' . $filename
        )
    ) {
        throw new Exception(
            'Could not save uploaded image.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Return Relative Image Path
    |--------------------------------------------------------------------------
    */

    return 'upload/products/' . $filename;
}

?>