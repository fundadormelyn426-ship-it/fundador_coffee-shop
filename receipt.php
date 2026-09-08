<?php

require_once 'config.php';

// Get order ID
$id = (int) ($_GET['id'] ?? 0);

// Get order details
$stmt = $pdo->prepare(
    'SELECT * FROM orders WHERE id = ?'
);
$stmt->execute([$id]);

$order = $stmt->fetch();

// If order does not exist, go back to shop
if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare(
    'SELECT * FROM order_items WHERE order_id = ?'
);
$stmt->execute([$id]);

$items = $stmt->fetchAll();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Order #<?= $order['id'] ?>
    </title>

    <link rel="stylesheet" href="style.css">

    <style>
        .receipt {
            max-width: 650px;
            margin: 50px auto;
            background: #fffaf3;
            padding: 30px;
            border-radius: 12px;
        }

        .receipt table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt td,
        .receipt th {
            padding: 10px;
            border-bottom: 1px solid #decbb7;
        }

        .right {
            text-align: right;
        }

        .btn {
            background: #28551e;
            color: #fff;
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <main class="receipt">

        <h1>
            Thank you for your order!
        </h1>

        <p>
            Order #<?= $order['id'] ?>
            ·
            <?= h($order['status']) ?>
        </p>

        <p>
            <?= h($order['customer_name']) ?>
            ·
            <?= h($order['customer_email']) ?>
        </p>

        <table>

            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th class="right">
                    Subtotal
                </th>
            </tr>

            <?php foreach ($items as $item): ?>

                <tr>

                    <td>
                        <?= h($item['product_name']) ?>
                    </td>

                    <td>
                        <?= $item['quantity'] ?>
                    </td>

                    <td class="right">
                        ₱<?= number_format(
                            $item['subtotal'],
                            2
                        ) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

        <h2 class="right">
            ₱<?= number_format($order['total'], 2) ?>
        </h2>

        <a
            class="btn"
            href="index.php"
        >
            Back to Shop
        </a>

        <button
            class="btn"
            onclick="window.print()"
        >
            Print
        </button>

    </main>

</body>

</html>