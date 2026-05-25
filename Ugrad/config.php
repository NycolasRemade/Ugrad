<?php
$host    = 'localhost';
$db      = 'Ugrad';
$user    = 'root';
$pass    = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Ativa notificações de erros estruturais
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna os dados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desativa emulação para maior segurança contra SQL Injection
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die('Erro na conexão com o banco de dados: ' . $e->getMessage());
}
?>