<?php
$host = 'localhost';
$port = '3306';
$db   = 'ugrad';
$user = 'root';
$pass = 'ugrad';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";
$options = [
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
     PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die('Erro na conexão: ' . $e->getMessage());
}
?>