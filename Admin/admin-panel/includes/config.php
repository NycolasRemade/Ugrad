<?php
/**
 * config.php
 * Conexão com o banco "ugrad" (ver dump SQL fornecido) via PDO.
 * Não altera o schema — apenas conecta e disponibiliza $pdo.
 *
 * Ajuste as constantes abaixo (ou defina variáveis de ambiente com
 * os mesmos nomes) conforme o seu servidor MySQL.
 */

// ---- Credenciais -----------------------------------------------------
// Prioriza variáveis de ambiente (ex.: definidas no Apache/nginx, no
// docker-compose, ou em um .env carregado antes deste arquivo). Se não
// existirem, cai nos valores padrão de desenvolvimento local.
define('DB_HOST', getenv('UGRAD_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('UGRAD_DB_PORT') ?: '3306');
define('DB_NAME', getenv('UGRAD_DB_NAME') ?: 'ugrad');
define('DB_USER', getenv('UGRAD_DB_USER') ?: 'root');
define('DB_PASS', getenv('UGRAD_DB_PASS') ?: '');

// ---- Conexão PDO -------------------------------------------------------
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // erros viram exceções, não warnings silenciosos
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // fetch já retorna array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                    // usa prepared statements reais (mais seguro)
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Em produção, não exponha a mensagem original do PDO (pode vazar
    // host/usuário). Log real deve ir para um arquivo de log.
    error_log('Falha de conexão com o banco ugrad: ' . $e->getMessage());
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados. Tente novamente mais tarde.');
}
