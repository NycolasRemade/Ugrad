<?php
/**
 * data.php
 * Camada de dados — consulta o banco "ugrad" via PDO.
 * (Sessão/permissão já são checadas em includes/auth_admin.php, exigido
 * antes deste arquivo em cada página de entrada — não repete a checagem
 * aqui pra não quebrar quando este arquivo é apenas incluído.)
 */

// Usa o config compartilhado do site (../Servidor/config.php) quando ele
// existir; se não (testando só esta pasta isolada, sem o site completo),
// cai no config.php local em includes/. Assim funciona nos dois casos.
$configCompartilhado = __DIR__ . '/../../Servidor/config.php';
if (file_exists($configCompartilhado)) {
    require_once $configCompartilhado;
} else {
    require_once __DIR__ . '/config.php';
}

/* ---------------------------------------------------------------------
 * Helpers genéricos
 * ------------------------------------------------------------------- */

function tipo_id(string $nome): ?int {
    global $pdo;
    static $cache = [];
    if (isset($cache[$nome])) return $cache[$nome];
    $stmt = $pdo->prepare('SELECT id FROM tipos_usuario WHERE nome = ?');
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();
    $cache[$nome] = $id !== false ? (int)$id : null;
    return $cache[$nome];
}

/** Rótulo amigável em pt-BR para os valores de tipos_usuario (que ficam em CAIXA_ALTA no banco). */
function tipo_label(string $tipoNome): string {
    $labels = [
        'ALUNO' => 'Aluno',
        'PROFESSOR' => 'Professor',
        'EMPRESARIO' => 'Investidor/Empresa',
        'INSTITUICAO' => 'Instituição',
        'ADMINISTRADOR' => 'Administrador',
    ];
    return $labels[$tipoNome] ?? ucfirst(strtolower($tipoNome));
}

/** Rótulo do estado do projeto (valores de proj_estado, em CAIXA_ALTA no banco). */
function estado_label(string $nome): string {
    $labels = [
        'PRIVADO_PRIVADO' => 'Privado / Privado',
        'PUBLICO_PRIVADO' => 'Público / Privado',
        'PUBLICO_PUBLICO' => 'Público / Público',
        'EM_REVISAO' => 'Em revisão',
    ];
    return $labels[$nome] ?? ucfirst(strtolower(str_replace('_', ' ', $nome)));
}

/** Rótulo do status de um membro de projeto (proj_membros_status). */
function membro_status_label(string $nome): string {
    $labels = [
        'DONO' => 'Dono',
        'MEMBRO' => 'Membro',
        'CONVITE_PENDENTE' => 'Convite pendente',
    ];
    return $labels[$nome] ?? ucfirst(strtolower(str_replace('_', ' ', $nome)));
}

function formatar_data(?string $timestamp): string {
    if (!$timestamp) return '—';
    $t = strtotime($timestamp);
    return $t ? date('d/m/Y', $t) : '—';
}

/** Converte o blob de imagem_perfil em data URI para uso direto num <img>. */
function avatar_data_uri(?string $blob): ?string {
    if (!$blob) return null;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($blob) ?: 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode($blob);
}

/* ---------------------------------------------------------------------
 * Instituições (usuários do tipo INSTITUICAO)
 * ------------------------------------------------------------------- */

function db_instituicoes(string $q = ''): array {
    global $pdo;
    $sql = 'SELECT u.id, u.nome, u.email, u.descricao, u.ativada, u.data_criacao
            FROM usuarios u
            JOIN tipos_usuario t ON t.id = u.tipo
            WHERE t.nome = \'INSTITUICAO\'';
    $params = [];
    if ($q !== '') {
        $sql .= ' AND u.nome LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY u.nome';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_instituicao(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.nome, u.email, u.descricao, u.ativada, u.data_criacao, u.imagem_perfil
                            FROM usuarios u
                            JOIN tipos_usuario t ON t.id = u.tipo
                            WHERE t.nome = \'INSTITUICAO\' AND u.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function professores_da_instituicao(int $instituicaoId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.nome, u.email
                            FROM usuarios u
                            JOIN tipos_usuario t ON t.id = u.tipo
                            JOIN extra_usuarios ex ON ex.id_usuario = u.id
                            WHERE t.nome = \'PROFESSOR\' AND ex.id_instituicao = ?
                            ORDER BY u.nome');
    $stmt->execute([$instituicaoId]);
    return $stmt->fetchAll();
}

function turmas_da_instituicao(int $instituicaoId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, nome FROM turmas WHERE id_instituicao = ? ORDER BY nome');
    $stmt->execute([$instituicaoId]);
    return $stmt->fetchAll();
}

/* ---------------------------------------------------------------------
 * Turmas
 * ------------------------------------------------------------------- */

function db_turmas(string $q = ''): array {
    global $pdo;
    $sql = 'SELECT tu.id, tu.nome, inst.nome AS instituicao_nome
            FROM turmas tu
            JOIN usuarios inst ON inst.id = tu.id_instituicao';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE tu.nome LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY tu.nome';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_turma(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT tu.id, tu.nome, tu.id_instituicao, inst.nome AS instituicao_nome
                            FROM turmas tu
                            JOIN usuarios inst ON inst.id = tu.id_instituicao
                            WHERE tu.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Contas vinculadas à turma (alunos e quem mais estiver nela). */
function membros_da_turma(int $turmaId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.nome, u.email, u.ativada, t.nome AS tipo_nome
                            FROM extra_usuarios ex
                            JOIN usuarios u ON u.id = ex.id_usuario
                            JOIN tipos_usuario t ON t.id = u.tipo
                            WHERE ex.id_turma = ?
                            ORDER BY u.nome');
    $stmt->execute([$turmaId]);
    return $stmt->fetchAll();
}

/** Códigos de acesso já gerados para a turma. */
function codigos_da_turma(int $turmaId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT c.codigo, c.data_criacao, t.nome AS tipo_nome
                            FROM codigo_instituicao c
                            JOIN tipos_usuario t ON t.id = c.tipo_usuario
                            WHERE c.id_turma = ?
                            ORDER BY c.data_criacao DESC');
    $stmt->execute([$turmaId]);
    return $stmt->fetchAll();
}

/** Exclui a turma: solta os alunos (ficam sem turma) e apaga os códigos dela. */
function turma_excluir(int $id): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE extra_usuarios SET id_turma = NULL WHERE id_turma = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM codigo_instituicao WHERE id_turma = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM turmas WHERE id = ?')->execute([$id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Falha ao excluir turma #' . $id . ': ' . $e->getMessage());
        return false;
    }
}

/* ---------------------------------------------------------------------
 * Projetos
 * ------------------------------------------------------------------- */

function db_projetos(string $q = ''): array {
    global $pdo;
    $sql = 'SELECT p.id, p.nome, p.data_criacao, pe.nome AS estado_nome
            FROM projetos p
            JOIN proj_estado pe ON pe.id = p.estado';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE p.nome LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY p.nome, p.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_projeto(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT p.id, p.nome, p.data_criacao, p.img,
                                  pe.nome AS estado_nome, pd.descricao, pd.historia
                            FROM projetos p
                            JOIN proj_estado pe ON pe.id = p.estado
                            LEFT JOIN proj_dados pd ON pd.id_projeto = p.id
                            WHERE p.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function categorias_do_projeto(int $projetoId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT c.id, c.nome
                            FROM proj_categorias pc
                            JOIN categorias c ON c.id = pc.id_categoria
                            WHERE pc.id_projeto = ?
                            ORDER BY c.nome');
    $stmt->execute([$projetoId]);
    return $stmt->fetchAll();
}

function membros_do_projeto(int $projetoId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.nome, st.nome AS status_nome
                            FROM proj_membros pm
                            JOIN usuarios u ON u.id = pm.id_convidado
                            JOIN proj_membros_status st ON st.id = pm.status_membro
                            WHERE pm.id_projeto = ?
                            ORDER BY st.id, u.nome');
    $stmt->execute([$projetoId]);
    return $stmt->fetchAll();
}

/** Denúncias feitas contra o projeto (tipo PROJETO). */
function reportagens_do_projeto(int $projetoId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT r.id, r.data_reportagem, ru.nome AS reportado_por
                            FROM reportagens r
                            JOIN usuarios ru ON ru.id = r.id_usuario
                            JOIN tipo_rep tr ON tr.id = r.tipo_rep
                            WHERE tr.nome = \'PROJETO\' AND r.id_reportado = ?
                            ORDER BY r.data_reportagem DESC');
    $stmt->execute([$projetoId]);
    return $stmt->fetchAll();
}

/* ---------------------------------------------------------------------
 * Usuários
 * ------------------------------------------------------------------- */

const USUARIO_SELECT = '
    SELECT u.id, u.nome, u.email, u.descricao, u.ativada, u.data_criacao, u.imagem_perfil,
           tu.nome AS tipo_nome,
           ex.id_turma, ex.id_instituicao,
           turma.nome AS turma_nome,
           inst.nome AS instituicao_nome,
           (SELECT p.nome
              FROM usuarios p
              JOIN tipos_usuario tp ON tp.id = p.tipo
              JOIN extra_usuarios pe ON pe.id_usuario = p.id
             WHERE tp.nome = \'PROFESSOR\' AND pe.id_turma = ex.id_turma
             LIMIT 1) AS professor_nome
    FROM usuarios u
    JOIN tipos_usuario tu ON tu.id = u.tipo
    LEFT JOIN extra_usuarios ex ON ex.id_usuario = u.id
    LEFT JOIN turmas turma ON turma.id = ex.id_turma
    LEFT JOIN usuarios inst ON inst.id = ex.id_instituicao
';

function db_usuarios(string $q = ''): array {
    global $pdo;
    $sql = USUARIO_SELECT . ' WHERE tu.nome != \'INSTITUICAO\'';
    $params = [];
    if ($q !== '') {
        $sql .= ' AND (u.nome LIKE :q1 OR u.email LIKE :q2)';
        $params[':q1'] = '%' . $q . '%';
        $params[':q2'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY u.nome, u.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_usuario(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare(USUARIO_SELECT . ' WHERE u.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Estatísticas de contas pra o dashboard: total cadastrado, ativas,
 * desativadas e a divisão por tipo (aluno, professor, etc. — sem
 * contar instituição, que tem sua própria contagem). Os campos
 * logaram_1h / logaram_24h só vêm preenchidos se a coluna
 * ultimo_acesso existir (ver migracao_ultimo_acesso.sql); senão são null.
 */
function db_estatisticas_contas(): array {
    global $pdo;

    $totais = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(ativada = 1) AS ativas,
            SUM(ativada = 0) AS desativadas
        FROM usuarios
        WHERE tipo != " . (int)tipo_id('INSTITUICAO')
    )->fetch();

    $porTipo = $pdo->query("
        SELECT tu.nome AS tipo_nome, COUNT(*) AS total
        FROM usuarios u
        JOIN tipos_usuario tu ON tu.id = u.tipo
        WHERE tu.nome != 'INSTITUICAO'
        GROUP BY tu.nome
        ORDER BY total DESC
    ")->fetchAll();

    $instituicoes = (int)$pdo->query("
        SELECT COUNT(*) FROM usuarios u
        JOIN tipos_usuario tu ON tu.id = u.tipo
        WHERE tu.nome = 'INSTITUICAO'
    ")->fetchColumn();

    // Logins recentes: só existe se a coluna ultimo_acesso foi criada (null = indisponível)
    $logaram1h = null;
    $logaram24h = null;
    if (coluna_ultimo_acesso_existe()) {
        $acessos = $pdo->query("
            SELECT
                SUM(ultimo_acesso >= NOW() - INTERVAL 1 HOUR) AS h1,
                SUM(ultimo_acesso >= NOW() - INTERVAL 24 HOUR) AS h24
            FROM usuarios
        ")->fetch();
        $logaram1h = (int)($acessos['h1'] ?? 0);
        $logaram24h = (int)($acessos['h24'] ?? 0);
    }

    return [
        'total' => (int)($totais['total'] ?? 0),
        'ativas' => (int)($totais['ativas'] ?? 0),
        'desativadas' => (int)($totais['desativadas'] ?? 0),
        'por_tipo' => $porTipo,
        'instituicoes' => $instituicoes,
        'logaram_1h' => $logaram1h,
        'logaram_24h' => $logaram24h,
    ];
}

/* ---------------------------------------------------------------------
 * Projetos do usuário
 * ------------------------------------------------------------------- */

function projetos_do_usuario(int $usuarioId): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT DISTINCT proj.id, proj.nome
                            FROM projetos proj
                            JOIN proj_membros pm ON pm.id_projeto = proj.id
                            JOIN proj_membros_status st ON st.id = pm.status_membro
                            WHERE pm.id_convidado = ? AND st.nome IN (\'DONO\', \'MEMBRO\')
                            ORDER BY proj.nome');
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

/* ---------------------------------------------------------------------
 * Avaliações / Reportagens
 * ------------------------------------------------------------------- */

function avaliacoes_do_usuario(int $usuarioId): array {
    global $pdo;

    $projetoIds = array_column(projetos_do_usuario($usuarioId), 'id');

    $sql = 'SELECT r.id, r.data_reportagem, ru.nome AS reportado_por, tr.nome AS tipo_rep_nome,
                   proj.nome AS projeto_nome
            FROM reportagens r
            JOIN usuarios ru ON ru.id = r.id_usuario
            JOIN tipo_rep tr ON tr.id = r.tipo_rep
            LEFT JOIN projetos proj ON proj.id = r.id_reportado AND tr.nome = \'PROJETO\'
            WHERE (tr.nome = \'USUARIO\' AND r.id_reportado = :uid)';

    $params = [':uid' => $usuarioId];

    if (!empty($projetoIds)) {
        $placeholders = [];
        foreach ($projetoIds as $i => $pid) {
            $key = ':p' . $i;
            $placeholders[] = $key;
            $params[$key] = $pid;
        }
        $sql .= ' OR (tr.nome = \'PROJETO\' AND r.id_reportado IN (' . implode(',', $placeholders) . '))';
    }

    $sql .= ' ORDER BY r.data_reportagem DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function nome_turma(?int $turmaId): string {
    global $pdo;
    if (!$turmaId) return '—';
    $stmt = $pdo->prepare('SELECT nome FROM turmas WHERE id = ?');
    $stmt->execute([$turmaId]);
    return $stmt->fetchColumn() ?: '—';
}

function nome_instituicao(?int $instituicaoId): string {
    global $pdo;
    if (!$instituicaoId) return '—';
    $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $stmt->execute([$instituicaoId]);
    return $stmt->fetchColumn() ?: '—';
}

/* ---------------------------------------------------------------------
 * Ações de escrita
 * ------------------------------------------------------------------- */

function usuario_toggle_ativacao(int $id, bool $ativar): bool {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET ativada = ? WHERE id = ?');
    return $stmt->execute([$ativar ? 1 : 0, $id]);
}

function usuario_excluir(int $id): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id FROM turmas WHERE id_instituicao = ?');
        $stmt->execute([$id]);
        $turmaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($turmaIds)) {
            $placeholders = implode(',', array_fill(0, count($turmaIds), '?'));
            $pdo->prepare("UPDATE extra_usuarios SET id_turma = NULL WHERE id_turma IN ($placeholders)")
                ->execute($turmaIds);
        }
        $pdo->prepare('UPDATE extra_usuarios SET id_instituicao = NULL WHERE id_instituicao = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM codigo_instituicao WHERE id_instituicao = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM turmas WHERE id_instituicao = ?')->execute([$id]);

        $pdo->prepare('DELETE FROM proj_membros WHERE id_convidante = ? OR id_convidado = ?')->execute([$id, $id]);
        $pdo->prepare('DELETE FROM comentarios WHERE id_usuario = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM reportagens WHERE id_usuario = ?')->execute([$id]);

        $tipoUsuarioId = tipo_id('USUARIO');
        if ($tipoUsuarioId) {
            $pdo->prepare('DELETE FROM reportagens WHERE tipo_rep = ? AND id_reportado = ?')
                ->execute([$tipoUsuarioId, $id]);
        }

        $pdo->prepare('DELETE FROM extra_usuarios WHERE id_usuario = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Falha ao excluir usuário #' . $id . ': ' . $e->getMessage());
        return false;
    }
}

/** Gera um código de acesso (opcionalmente já amarrado a uma turma). Devolve o código ou null. */
function instituicao_gerar_codigo(int $instituicaoId, string $tipoUsuarioNome = 'ALUNO', ?int $turmaId = null): ?string {
    global $pdo;
    $tipoId = tipo_id($tipoUsuarioNome);
    if (!$tipoId) return null;

    $stmt = $pdo->prepare('INSERT INTO codigo_instituicao (id_instituicao, codigo, tipo_usuario, id_turma) VALUES (?, ?, ?, ?)');
    // o código é UNIQUE: no caso (raro) de colisão, tenta de novo
    for ($tentativa = 0; $tentativa < 5; $tentativa++) {
        $codigo = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        try {
            $stmt->execute([$instituicaoId, $codigo, $tipoId, $turmaId]);
            return $codigo;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                error_log('Falha ao gerar código: ' . $e->getMessage());
                return null;
            }
        }
    }
    return null;
}

/**
 * Redefinição de senha: gera uma senha temporária, grava (com hash) e
 * tenta mandar por e-mail. Se o servidor não tiver e-mail configurado
 * (comum em XAMPP), devolve a senha pra o admin repassar ao usuário.
 * Retorno: ['ok' => bool, 'enviado' => bool, 'senha' => string|null]
 */
function usuario_resetar_senha(int $id, string $email, string $nome): array {
    global $pdo;

    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'; // sem caracteres confusos (0/O, 1/l/I)
    $senha = '';
    for ($i = 0; $i < 10; $i++) {
        $senha .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }

    try {
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $stmt->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
    } catch (PDOException $e) {
        error_log('Falha ao redefinir senha #' . $id . ': ' . $e->getMessage());
        return ['ok' => false, 'enviado' => false, 'senha' => null];
    }

    $enviado = false;
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $de = defined('MAIL_FROM') ? MAIL_FROM : 'nao-responda@ugrad.local';
        $assunto = '=?UTF-8?B?' . base64_encode('Ugrad: sua nova senha temporária') . '?=';
        $corpo = "Olá, $nome!\n\n"
               . "Um administrador redefiniu a senha da sua conta no Ugrad.\n"
               . "Senha temporária: $senha\n\n"
               . "Entre com ela e troque por uma senha só sua o quanto antes.\n";
        $cabecalhos = "From: $de\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $enviado = @mail($email, $assunto, $corpo, $cabecalhos);
    }

    return ['ok' => true, 'enviado' => $enviado, 'senha' => $enviado ? null : $senha];
}

/* ---------------------------------------------------------------------
 * Registro de acessos (opcional)
 * O banco original não guarda quando alguém logou. Rodando
 * migracao_ultimo_acesso.sql uma vez, a coluna usuarios.ultimo_acesso
 * passa a existir; aí registrar_acesso() grava o login e o dashboard
 * mostra quantas contas logaram. Sem a coluna, tudo isso fica
 * desligado e nada quebra.
 * ------------------------------------------------------------------- */

function coluna_ultimo_acesso_existe(): bool {
    global $pdo;
    static $existe = null;
    if ($existe !== null) return $existe;
    try {
        $existe = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_acesso'")->fetch();
    } catch (PDOException $e) {
        $existe = false;
    }
    return $existe;
}

/** Chame logo após um login bem-sucedido (o login do site todo pode chamar isso também). */
function registrar_acesso(int $usuarioId): void {
    global $pdo;
    if (!coluna_ultimo_acesso_existe()) return;
    try {
        $pdo->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?')->execute([$usuarioId]);
    } catch (PDOException $e) {
        error_log('Falha ao registrar acesso #' . $usuarioId . ': ' . $e->getMessage());
    }
}

/* ---------------------------------------------------------------------
 * Moderação de reportagens
 * ------------------------------------------------------------------- */

function tipo_rep_id(string $nome): ?int {
    global $pdo;
    static $cache = [];
    if (isset($cache[$nome])) return $cache[$nome];
    $stmt = $pdo->prepare('SELECT id FROM tipo_rep WHERE nome = ?');
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();
    $cache[$nome] = $id !== false ? (int)$id : null;
    return $cache[$nome];
}

function reportagem_find(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT r.id, r.id_usuario, r.id_reportado, tr.nome AS tipo_rep_nome
                            FROM reportagens r
                            JOIN tipo_rep tr ON tr.id = r.tipo_rep
                            WHERE r.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function reportagem_ignorar(int $id): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare('DELETE FROM reportagens WHERE id = ?');
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function projeto_excluir(int $id): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM proj_imagens WHERE id_projeto = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM proj_categorias WHERE id_projeto = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM proj_membros WHERE id_projeto = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM comentarios WHERE id_projeto = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM proj_dados WHERE id_projeto = ?')->execute([$id]);

        $tipoProjetoId = tipo_rep_id('PROJETO');
        if ($tipoProjetoId) {
            $pdo->prepare('DELETE FROM reportagens WHERE tipo_rep = ? AND id_reportado = ?')
                ->execute([$tipoProjetoId, $id]);
        }

        $pdo->prepare('DELETE FROM projetos WHERE id = ?')->execute([$id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Falha ao excluir projeto #' . $id . ': ' . $e->getMessage());
        return false;
    }
}

function comentario_excluir(int $id): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare('DELETE FROM comentarios WHERE id = ?');
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Apaga o item denunciado. Além de success/message, devolve qual item
 * sumiu (removido_tipo = 'usuario' | 'projeto' | 'comentario' | null e
 * removido_id) pra tela tirar o item da lista na hora.
 */
function reportagem_apagar(int $id): array {
    $rep = reportagem_find($id);
    if (!$rep) {
        return ['success' => false, 'message' => 'Reportagem não encontrada.', 'usuario_removido' => false,
                'removido_tipo' => null, 'removido_id' => null];
    }

    $ok = true;
    $removidoTipo = null;
    $removidoId = (int)$rep['id_reportado'];

    switch ($rep['tipo_rep_nome']) {
        case 'PROJETO':
            $ok = projeto_excluir($removidoId);
            $removidoTipo = $ok ? 'projeto' : null;
            $message = $ok ? 'Projeto reportado excluído.' : 'Não foi possível excluir o projeto reportado (há dados vinculados).';
            break;
        case 'USUARIO':
            $ok = usuario_excluir($removidoId);
            $removidoTipo = $ok ? 'usuario' : null;
            $message = $ok ? 'Usuário reportado excluído.' : 'Não foi possível excluir o usuário reportado (há dados vinculados).';
            break;
        case 'COMENTARIO':
            $ok = comentario_excluir($removidoId);
            $removidoTipo = $ok ? 'comentario' : null;
            $message = $ok ? 'Comentário reportado excluído.' : 'Não foi possível excluir o comentário reportado.';
            break;
        default:
            $message = 'Reportagem removida.';
    }

    if ($ok) {
        reportagem_ignorar($id);
    }

    return [
        'success' => $ok,
        'message' => $message,
        'usuario_removido' => $removidoTipo === 'usuario',
        'removido_tipo' => $removidoTipo,
        'removido_id' => $removidoTipo ? $removidoId : null,
    ];
}

function total_reportagens(): int {
    global $pdo;
    return (int)$pdo->query('SELECT COUNT(*) FROM reportagens')->fetchColumn();
}

function flash_set(string $msg): void { $_SESSION['flash'] = $msg; }
function flash_get(): ?string {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}