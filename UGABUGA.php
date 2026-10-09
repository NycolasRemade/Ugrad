<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
session_start();
require_once 'Servidor/config.php';
if ($_SESSION['usuario_id'] != 5) {
    header('Location: dashboard.php');
    exit;
}

for ($i = 0; $i < 100; $i++) {
    $stmt = $pdo->query("INSERT INTO projetos (nome, estado) VALUES (teste$i, 3)");
    $stmt2 = $pdo->query("INSERT INTO proj_membros (id_convidante, id_convidado, status_membro) VALUES (1, 1, 1), (1, 2, 2)");
}