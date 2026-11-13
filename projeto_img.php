<?php
session_start();
require_once 'Servidor/config.php';

$id_imagem = (int)($_GET['id'] ?? 0);
if ($id_imagem <= 0) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare(
   'SELECT pi.img, p.estado, p.id
    FROM proj_imagens pi
    JOIN projetos p ON pi.id_projeto = p.id
    WHERE pi.id = ?'
);
$stmt->execute([$id_imagem]);
$registro = $stmt->fetch();

if (!$registro || empty($registro['img'])) {
    http_response_code(404);
    exit;
}

// Bloqueia acesso se o usuário não estiver logado e o projeto não for público
if (!isset($_SESSION['usuario_id'])) {
    if ((int)$registro['estado'] !== 3) {
        http_response_code(403);
        exit;
    }
} else {
    $stmt = $pdo->prepare(
       'SELECT pm.id 
        FROM proj_membros pm JOIN projetos p 
        ON pm.id_projeto = p.id
        WHERE p.id = ? AND pm.id_convidado = ?'
    );
    $stmt->execute([$registro['id'], $_SESSION['usuario_id']]);

    if (!$stmt->fetch() || (int)$registro['estado'] !== 3) {
        http_response_code(403);
        exit;
    }
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=86400');
echo $registro['img'];
exit;