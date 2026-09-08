<?php

require_once 'config.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT *
     FROM orders
     WHERE id = ?'
);

$stmt->execute([$id]);

$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

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
        Order Successful - Fundador Coffee
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .success {
            max-width: 600px;
            margin: 70px auto;
            padding: 40px;
            text-align: center;
            background: #fffaf3;
            border-radius: 16px;
            box-shadow: 0 10px 30px #0001;
        }

        .success .check {
            font-size: 60px;
        }

        .success a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            background: #28551e;
            color: #fff;
            text-decoration: none;
        }

    </style>

</head>

<body style="background:#f6ecdf">

    <main class="success">

        <div class="check">
            ✓
        </div>

        <h1>
            Order Successful!
        </h1>

        <p>
            Your order
            <b>#<?= h($order['id']) ?></b>
            has been received.
        </p>

        <p>
            Total:
            <b>
                ₱<?= number_format($order['total'], 2) ?>
            </b>
        </p>

        <a href="receipt.php?id=<?= (int) $order['id'] ?>">
            View Receipt
        </a>

    </main>

</body>

</html>