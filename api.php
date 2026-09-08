<?php

require_once 'config.php';

header('Content-Type: application/json');

/**
 * Return a JSON response and stop execution.
 */
function out($ok, $message = '', $data = [])
{
    echo json_encode([
        'success' => $ok,
        'message' => $message,
        'data'    => $data,
    ]);

    exit;
}

/**
 * Get the current shopping cart.
 */
function getCart($pdo)
{
    $cart = $_SESSION['cart'] ?? [];

    $items = [];
    $total = 0;
    $count = 0;

    foreach ($cart as $id => $qty) {

        $id  = (int) $id;
        $qty = (int) $qty;

        if ($qty < 1) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        $stmt = $pdo->prepare(
            'SELECT id, name, price, image, stock
             FROM products
             WHERE id = ? AND is_active = 1'
        );

        $stmt->execute([$id]);

        $product = $stmt->fetch();

        // Product no longer exists or is out of stock.
        if (!$product || (int) $product['stock'] < 1) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        // Never allow cart quantity to exceed available stock.
        $qty = min($qty, (int) $product['stock']);

        $_SESSION['cart'][$id] = $qty;

        $subtotal = (float) $product['price'] * $qty;

        $total += $subtotal;
        $count += $qty;

        $items[] = [
            'id'       => (int) $product['id'],
            'name'     => $product['name'],
            'price'    => (float) $product['price'],
            'image'    => $product['image'],
            'stock'    => (int) $product['stock'],
            'qty'      => $qty,
            'subtotal' => $subtotal,
        ];
    }

    return [
        'items' => $items,
        'total' => $total,
        'count' => $count,
    ];
}


/*
|--------------------------------------------------------------------------
| Get requested action
|--------------------------------------------------------------------------
*/

$action = $_POST['action'] ?? $_GET['action'] ?? '';


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

if ($action === 'cart') {

    out(
        true,
        '',
        getCart($pdo)
    );
}


/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

if ($action === 'add') {

    $id = (int) ($_POST['product_id'] ?? 0);

    $qty = max(
        1,
        (int) ($_POST['qty'] ?? 1)
    );

    $stmt = $pdo->prepare(
        'SELECT *
         FROM products
         WHERE id = ? AND is_active = 1'
    );

    $stmt->execute([$id]);

    $product = $stmt->fetch();

    if (!$product) {
        out(false, 'Product not found.');
    }

    if ((int) $product['stock'] < 1) {
        out(false, 'This product is out of stock.');
    }

    $newQty = (
        $_SESSION['cart'][$id] ?? 0
    ) + $qty;

    if ($newQty > (int) $product['stock']) {
        out(
            false,
            "Only {$product['stock']} left in stock."
        );
    }

    $_SESSION['cart'][$id] = $newQty;

    out(
        true,
        'Added to your order.',
        getCart($pdo)
    );
}


/*
|--------------------------------------------------------------------------
| UPDATE CART
|--------------------------------------------------------------------------
*/

if ($action === 'update') {

    $id  = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);

    // Quantity 0 removes the item.
    if ($qty <= 0) {

        unset($_SESSION['cart'][$id]);

    } else {

        $stmt = $pdo->prepare(
            'SELECT stock
             FROM products
             WHERE id = ? AND is_active = 1'
        );

        $stmt->execute([$id]);

        $product = $stmt->fetch();

        if (!$product) {
            out(false, 'Product unavailable.');
        }

        if ($qty > (int) $product['stock']) {
            out(
                false,
                "Only {$product['stock']} left in stock."
            );
        }

        $_SESSION['cart'][$id] = $qty;
    }

    out(
        true,
        'Cart updated.',
        getCart($pdo)
    );
}


/*
|--------------------------------------------------------------------------
| REMOVE FROM CART
|--------------------------------------------------------------------------
*/

if ($action === 'remove') {

    $id = (int) ($_POST['product_id'] ?? 0);

    unset($_SESSION['cart'][$id]);

    out(
        true,
        'Removed.',
        getCart($pdo)
    );
}


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

if ($action === 'checkout') {

    if (empty($_SESSION['cart'])) {
        out(false, 'Your cart is empty.');
    }

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    if (
        !$name ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        out(
            false,
            'Enter a valid name and email.'
        );
    }

    try {

        /*
         * Start transaction so stock and orders
         * are updated safely together.
         */
        $pdo->beginTransaction();

        $locked = [];
        $total  = 0;

        /*
         * Lock products while checking stock.
         */
        foreach ($_SESSION['cart'] as $id => $qty) {

            $id  = (int) $id;
            $qty = (int) $qty;

            if ($qty < 1) {
                continue;
            }

            $stmt = $pdo->prepare(
                'SELECT *
                 FROM products
                 WHERE id = ? AND is_active = 1
                 FOR UPDATE'
            );

            $stmt->execute([$id]);

            $product = $stmt->fetch();

            if (
                !$product ||
                (int) $product['stock'] < $qty
            ) {
                throw new Exception(
                    'Stock changed. Please review your cart.'
                );
            }

            $subtotal =
                (float) $product['price'] * $qty;

            $total += $subtotal;

            $locked[] = [
                'id'       => (int) $product['id'],
                'name'     => $product['name'],
                'price'    => (float) $product['price'],
                'qty'      => $qty,
                'subtotal' => $subtotal,
            ];
        }

        if (empty($locked)) {
            throw new Exception(
                'Your cart is empty.'
            );
        }


        /*
         * Create the order.
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
            $total,
        ]);

        $orderId = $pdo->lastInsertId();


        /*
         * Prepare order item insert.
         */
        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, product_id, product_name, price, quantity, subtotal)
             VALUES
                (?, ?, ?, ?, ?, ?)'
        );


        /*
         * Prepare stock update.
         */
        $stockStmt = $pdo->prepare(
            'UPDATE products
             SET stock = stock - ?
             WHERE id = ?'
        );


        /*
         * Save every item and deduct stock.
         */
        foreach ($locked as $item) {

            $itemStmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['qty'],
                $item['subtotal'],
            ]);

            $stockStmt->execute([
                $item['qty'],
                $item['id'],
            ]);
        }


        /*
         * Everything succeeded.
         */
        $pdo->commit();

        // Clear the customer's cart.
        $_SESSION['cart'] = [];

        out(
            true,
            'Order placed.',
            [
                'order_id' => $orderId,
            ]
        );

    } catch (Throwable $e) {

        /*
         * Undo database changes if anything failed.
         */
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        out(
            false,
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| INVALID ACTION
|--------------------------------------------------------------------------
*/

out(
    false,
    'Invalid action.'
);