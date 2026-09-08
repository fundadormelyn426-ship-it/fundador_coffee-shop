<?php

session_start();

$host = '127.0.0.1';
$db   = 'fundador_coffee';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    die(
        'Database connection failed. '
        . 'Create the fundador_coffee database and import db.sql.'
    );
}

/**
 * Escape HTML output.
 */
function h($v)
{
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Require the user to be logged in.
 */
function require_login()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require administrator access.
 */
function require_admin()
{
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: login.php?error=Admin+access+required');
        exit;
    }
}

/*
 * Load helper functions.
 *
 * IMPORTANT:
 * Make sure the file is actually named:
 * functions.php
 */
require_once __DIR__ . '/functions.php';