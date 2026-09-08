<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get login details
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Find user by email
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE email = ?'
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    // Verify login
    if (
        $user &&
        password_verify($password, $user['password'])
    ) {

        // Store user information in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {

            header('Location: admin.php');

        } else {

            header('Location: index.php');
        }

        exit;
    }

    // Login failed
    $error = 'Invalid email or password.';
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

    <title>Login - Fundador</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .auth {
            max-width: 430px;
            margin: 70px auto;
            background: #fffaf3;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px #0002;
        }

        .auth input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin: 7px 0;
            border: 1px solid #cdb9a5;
            border-radius: 7px;
        }

        .auth button {
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 7px;
            background: #28551e;
            color: #fff;
            font-weight: bold;
            margin-top: 8px;
            cursor: pointer;
        }

        .auth a {
            color: #28551e;
        }

    </style>

</head>

<body>

    <main class="auth">

        <h1>
            Fundador Login
        </h1>

        <?php if (isset($error)): ?>

            <p>
                <?= h($error) ?>
            </p>

        <?php endif; ?>

        <form method="post">

            <input
                type="email"
                name="email"
                placeholder="Email"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <p>
            No account?
            <a href="register.php">
                Register
            </a>
        </p>

        <p>
            <a href="index.php">
                Back to shop
            </a>
        </p>

    </main>

</body>

</html>