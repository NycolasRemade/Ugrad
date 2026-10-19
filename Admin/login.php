<?php
/**
 * login.php — ESTE ARQUIVO É SÓ PRA TESTE LOCAL.
 *
 * O login de verdade é uma página do site completo, fora desta pasta
 * (é pra isso que o auth_admin.php redireciona). Como aqui você só
 * tem a pasta do admin-panel isolada, sem o resto do site, esse
 * arquivo simula um login de administrador só pra você conseguir
 * testar as telas. APAGUE este arquivo (ou troque pelo login real)
 * antes de colocar em produção.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ao abrir esta página, já entra como administrador (tipo 5) de teste.
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_tipo'] = 5;

header('Location: index.php');
exit;
