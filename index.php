<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}
if (isset($_GET['codigo']) || isset($_GET['professor']) || isset($_GET['turma'])) {
    header(
        'Location: codigo_instituicao.php?codigo=' . 
        ($_GET['codigo'] ?? $_GET['professor'] ?? $_GET['turma'])
    );
    exit;
}

header('Location: Ugrad.html');