<?php

require_once 'config.php';

require_admin();

$msg = '';
$err = '';


/*
|--------------------------------------------------------------------------
| Handle POST actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | RESTOCK PRODUCT
        |--------------------------------------------------------------------------
        */

        if ($action === 'restock') {

            $qty = (int) ($_POST['qty'] ?? 0);
            $id  = (int) ($_POST['id'] ?? 0);

            $product = add_stock(
                $pdo,
                $id,
                $qty
            );

            $msg =
                'Stock restocked successfully. New stock: '
                . (int) $product['stock']
                . '.';
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE PRODUCT
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'save') {

            $id = (int) ($_POST['id'] ?? 0);

            $data = [
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                max(0, (float) ($_POST['price'] ?? 0)),
                trim($_POST['image'] ?? ''),
                trim($_POST['category'] ?? 'coffee'),
                max(0, (int) ($_POST['stock'] ?? 0)),
                isset($_POST['active']) ? 1 : 0,
            ];


            /*
            |--------------------------------------------------------------------------
            | Update existing product
            |--------------------------------------------------------------------------
            */

            if ($id) {

                $stmt = $pdo->prepare(
                    'UPDATE products
                     SET name = ?,
                         description = ?,
                         price = ?,
                         image = ?,
                         category = ?,
                         stock = ?,
                         is_active = ?
                     WHERE id = ?'
                );

                $stmt->execute([
                    ...$data,
                    $id,
                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | Create new product
            |--------------------------------------------------------------------------
            */

            else {

                $stmt = $pdo->prepare(
                    'INSERT INTO products
                        (name, description, price, image, category, stock, is_active)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute($data);
            }

            $msg = 'Product saved.';
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER STATUS
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'status') {

            $status = $_POST['status'] ?? '';
            $id     = (int) ($_POST['id'] ?? 0);

            $allowedStatuses = [
                'Pending',
                'Preparing',
                'Ready',
                'Completed',
                'Cancelled',
            ];

            if (!in_array($status, $allowedStatuses, true)) {
                throw new Exception('Invalid order status.');
            }

            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET status = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $status,
                $id,
            ]);

            $msg = 'Order status updated.';
        }

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| Get products
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query(
        'SELECT *
         FROM products
         ORDER BY id DESC'
    )
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| Get orders
|--------------------------------------------------------------------------
*/

$orders = $pdo
    ->query(
        "SELECT
            o.*,
            GROUP_CONCAT(
                CONCAT(
                    oi.product_name,
                    ' x',
                    oi.quantity
                )
                ORDER BY oi.id
                SEPARATOR ' | '
            ) AS items
         FROM orders o
         LEFT JOIN order_items oi
            ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY o.created_at DESC"
    )
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| Get product for editing
|--------------------------------------------------------------------------
*/

$edit = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare(
        'SELECT *
         FROM products
         WHERE id = ?'
    );

    $stmt->execute([
        (int) $_GET['edit'],
    ]);

    $edit = $stmt->fetch();
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

    <title>Admin Dashboard</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .admin {
            padding: 28px;
            width: min(1200px, 94%);
            margin: auto;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
        }

        .box {
            background: #fffaf3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form {
            display: grid;
            gap: 9px;
        }

        .form input,
        .form textarea,
        .form select {
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        .btn {
            background: #28551e;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 8px 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table td,
        .table th {
            padding: 10px;
            border-bottom: 1px solid #dfcdb9;
            text-align: left;
        }

        .restock {
            display: flex;
            gap: 5px;
            margin-top: 6px;
        }

        .restock input {
            width: 80px;
        }

        .out {
            color: #a22;
            font-weight: bold;
        }

        @media (max-width: 850px) {

            .admin-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<header class="header">

    <div class="nav">

        <a
            class="logo"
            href="index.php"
        >

            <span class="cup">☕</span>

            <span>
                <b>FUNDADOR</b>
                <small>COFFEE SHOP</small>
            </span>

        </a>


        <nav>

            <a href="index.php">
                Shop
            </a>

            <a href="history.php">
                Order History
            </a>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>


<main class="admin">

    <h1>
        Admin Dashboard
    </h1>


    <?php if ($msg): ?>

        <p>
            <?= h($msg) ?>
        </p>

    <?php endif; ?>


    <?php if ($err): ?>

        <p class="out">
            <?= h($err) ?>
        </p>

    <?php endif; ?>


    <div class="admin-grid">


        <!-- =========================================================
             PRODUCT FORM
        ========================================================== -->

        <section class="box">

            <h2>
                <?= $edit ? 'Edit Product' : 'Add Product' ?>
            </h2>


            <form
                class="form"
                method="post"
            >

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) ($edit['id'] ?? 0) ?>"
                >


                <input
                    name="name"
                    placeholder="Name"
                    required
                    value="<?= h($edit['name'] ?? '') ?>"
                >


                <textarea
                    name="description"
                    placeholder="Description"
                ><?= h($edit['description'] ?? '') ?></textarea>


                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    placeholder="Price"
                    required
                    value="<?= h($edit['price'] ?? '') ?>"
                >


                <input
                    type="number"
                    min="0"
                    name="stock"
                    placeholder="Stock"
                    required
                    value="<?= h($edit['stock'] ?? 0) ?>"
                >


                <input
                    name="image"
                    placeholder="assets/latte.jpg"
                    required
                    value="<?= h($edit['image'] ?? '') ?>"
                >


                <input
                    name="category"
                    placeholder="Category"
                    value="<?= h($edit['category'] ?? 'coffee') ?>"
                >


                <label>

                    <input
                        type="checkbox"
                        name="active"
                        <?= (
                            !$edit ||
                            !empty($edit['is_active'])
                        ) ? 'checked' : '' ?>
                    >

                    Active

                </label>


                <button
                    class="btn"
                    type="submit"
                >
                    Save Product
                </button>

            </form>

        </section>



        <!-- =========================================================
             PRODUCTS / INVENTORY
        ========================================================== -->

        <section class="box">

            <h2>
                Products & Inventory
            </h2>


            <div style="overflow:auto">

                <table class="table">

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>


                    <?php foreach ($products as $product): ?>

                        <tr>

                            <td>
                                <?= h($product['name']) ?>
                            </td>


                            <td>
                                ₱<?= number_format(
                                    $product['price'],
                                    2
                                ) ?>
                            </td>


                            <td
                                class="<?= (int) $product['stock'] === 0
                                    ? 'out'
                                    : '' ?>"
                            >

                                <?= (int) $product['stock'] ?>

                                <?php if ((int) $product['stock'] === 0): ?>

                                    Out of Stock

                                <?php endif; ?>


                                <form
                                    class="restock"
                                    method="post"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="restock"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="qty"
                                        min="1"
                                        placeholder="+ qty"
                                        required
                                    >

                                    <button
                                        class="btn"
                                        type="submit"
                                    >
                                        Restock
                                    </button>

                                </form>

                            </td>


                            <td>

                                <a
                                    class="btn"
                                    href="admin.php?edit=<?= (int) $product['id'] ?>"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        </section>

    </div>



    <!-- =============================================================
         ORDERS
    ============================================================= -->

    <section class="box">

        <h2>
            Orders
        </h2>


        <div style="overflow:auto">

            <table class="table">

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Products Ordered
                    </th>

                    <th>
                        Total
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Date
                    </th>

                    <th></th>

                </tr>


                <?php foreach ($orders as $order): ?>

                    <tr>

                        <td>
                            #<?= (int) $order['id'] ?>
                        </td>


                        <td>

                            <b>
                                <?= h($order['customer_name']) ?>
                            </b>

                            <br>

                            <small>
                                <?= h($order['customer_email']) ?>
                            </small>

                        </td>


                        <td>
                            <?= h($order['items'] ?? '') ?>
                        </td>


                        <td>
                            ₱<?= number_format(
                                $order['total'],
                                2
                            ) ?>
                        </td>


                        <td>

                            <form method="post">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="status"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $order['id'] ?>"
                                >


                                <select
                                    name="status"
                                    onchange="this.form.submit()"
                                >

                                    <?php
                                    $statuses = [
                                        'Pending',
                                        'Preparing',
                                        'Ready',
                                        'Completed',
                                        'Cancelled',
                                    ];
                                    ?>

                                    <?php foreach ($statuses as $status): ?>

                                        <option
                                            value="<?= h($status) ?>"
                                            <?= $order['status'] === $status
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= h($status) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </form>

                        </td>


                        <td>
                            <?= h($order['created_at']) ?>
                        </td>


                        <td>

                            <a
                                class="btn"
                                href="history.php?order=<?= (int) $order['id'] ?>"
                            >
                                View
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </section>

</main>

</body>
</html>