<?php
session_start();
$host='127.0.0.1'; $db='fundador_coffee'; $user='root'; $pass='';
try { $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
catch(PDOException $e){ http_response_code(500); die('Database connection failed. Create the fundador_coffee database and import db.sql.'); }
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function require_login(){if(empty($_SESSION['user_id'])){header('Location: login.php');exit;}}
function require_admin(){if(($_SESSION['role']??'')!=='admin'){header('Location: login.php?error=Admin+access+required');exit;}}

require_once __DIR__.'/function.php';
