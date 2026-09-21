<?php
/**
 * data.php
 * Camada de dados — agora consulta o banco "ugrad" de verdade via PDO
 * (schema fornecido: usuarios, tipos_usuario, turmas, extra_usuarios,
 * projetos, proj_membros, reportagens, etc). Nenhum dado fictício aqui;
 * tudo que aparece na tela vem do banco.
 *
 * Observações sobre limitações do schema (não inventamos colunas):
 * - Não existe coluna de "último login" em `usuarios` — o campo foi
 *   removido das telas. Se quiser rastrear isso, precisa de uma coluna
 *   nova (ex.: `ultimo_login TIMESTAMP NULL`).
 * - `reportagens` não tem texto/motivo nem status (ativo/resolvido) —
 *   as avaliações mostram quem reportou, quando, e o que foi reportado
 *   (projeto ou perfil do usuário), sem o texto/etiqueta que existia
 *   no mock. Se quiser esses campos, precisa adicionar colunas como
 *   `motivo TEXT` e `status` em `reportagens`.
 * - "Professor da turma" é inferido via `extra_usuarios` (usuário do
 *   tipo PROFESSOR com o mesmo id_turma), pois não há coluna de
 *   professor em `turmas`.
 */

session_start();
require '../config.php'; // disponibiliza $pdo

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
    $stmt = $pdo->prepare('SELECT u.id, u.nome, u.email, u.descricao, u.ativada, u.data_criacao, u.imagem_perfil,
                                  (SELECT GROUP_CONCAT(codigo ORDER BY data_criacao DESC SEPARATOR \', \') 
                                   FROM codigo_instituicao 
                                   WHERE id_instituicao = u.id) AS codigo
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

/* ---------------------------------------------------------------------
 * Usuários (todos os tipos: aluno, professor, empresário, instituição,
 * administrador)
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
    // Instituições já aparecem na seção "Instituições" da busca — não
    // duplica elas aqui na lista de usuários.
    $sql = USUARIO_SELECT . ' WHERE tu.nome != \'INSTITUICAO\'';
    $params = [];
    if ($q !== '') {
        $sql .= ' AND (u.nome LIKE :q1 OR u.email LIKE :q2)';
        $params[':q1'] = '%' . $q . '%';
        $params[':q2'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY u.nome';
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

/* ---------------------------------------------------------------------
 * Projetos do usuário (dono ou membro aceito)
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
 * "Avaliações" do usuário = reportagens feitas contra ele ou contra
 * projetos dele (ver observações no topo do arquivo sobre os campos
 * que o schema atual não possui).
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

/* ---------------------------------------------------------------------
 * Lookups usados nas telas de detalhe
 * ------------------------------------------------------------------- */

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
 * Ações de escrita usadas pelos botões das telas de detalhe
 * ------------------------------------------------------------------- */

function usuario_toggle_ativacao(int $id, bool $ativar): bool {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET ativada = ? WHERE id = ?');
    return $stmt->execute([$ativar ? 1 : 0, $id]);
}

/**
 * Exclui o usuário "de verdade", removendo antes tudo que aponta pra
 * ele via foreign key (o schema não usa ON DELETE CASCADE, então um
 * DELETE simples falha quase sempre). Tudo roda numa transação: se
 * qualquer passo falhar, nada é apagado.
 */
function usuario_excluir(int $id): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();

        // Se for uma instituição: desvincula turmas/usuários dela antes de apagar.
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

        // Participações em projetos, comentários e reportagens feitas por este usuário.
        $pdo->prepare('DELETE FROM proj_membros WHERE id_convidante = ? OR id_convidado = ?')->execute([$id, $id]);
        $pdo->prepare('DELETE FROM comentarios WHERE id_usuario = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM reportagens WHERE id_usuario = ?')->execute([$id]);

        // Reportagens feitas CONTRA este usuário (id_reportado não tem FK, mas fica órfã se não limpar).
        $tipoUsuarioId = tipo_id('USUARIO');
        if ($tipoUsuarioId) {
            $pdo->prepare('DELETE FROM reportagens WHERE tipo_rep = ? AND id_reportado = ?')
                ->execute([$tipoUsuarioId, $id]);
        }

        // Vínculo de turma/instituição do próprio usuário.
        $pdo->prepare('DELETE FROM extra_usuarios WHERE id_usuario = ?')->execute([$id]);

        // Por fim, o usuário.
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

function instituicao_gerar_codigo(int $instituicaoId, string $tipoUsuarioNome = 'ALUNO'): ?string {
    global $pdo;
    $tipoId = tipo_id($tipoUsuarioNome);
    if (!$tipoId) return null;

    $codigo = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $stmt = $pdo->prepare('INSERT INTO codigo_instituicao (id_instituicao, codigo, tipo_usuario) VALUES (?, ?, ?)');
    $stmt->execute([$instituicaoId, $codigo, $tipoId]);
    return $codigo;
}

/* ---------------------------------------------------------------------
 * Moderação de reportagens (botões "Apagar" / "Ignorar" nos cards de
 * Avaliações). "Ignorar" só descarta a reportagem. "Apagar" remove o
 * conteúdo reportado de fato (o projeto, o comentário, ou o usuário,
 * dependendo do tipo) e, junto, a própria reportagem.
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

/** Descarta a reportagem sem mexer no conteúdo reportado. */
function reportagem_ignorar(int $id): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare('DELETE FROM reportagens WHERE id = ?');
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/** Exclui um projeto e tudo que depende dele (cascata em código, já que o schema não tem ON DELETE CASCADE). */
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
 * Botão "Apagar": exclui o item que foi reportado (projeto, comentário
 * ou o próprio usuário, conforme o tipo da reportagem) e, se der certo,
 * a reportagem também. Devolve o que aconteceu pra tela decidir se
 * precisa tirar alguma coisa da lista/painel.
 */
function reportagem_apagar(int $id): array {
    $rep = reportagem_find($id);
    if (!$rep) {
        return ['success' => false, 'message' => 'Reportagem não encontrada.', 'usuario_removido' => false];
    }

    $ok = true;
    $usuarioRemovido = false;

    switch ($rep['tipo_rep_nome']) {
        case 'PROJETO':
            $ok = projeto_excluir((int)$rep['id_reportado']);
            $message = $ok ? 'Projeto reportado excluído.' : 'Não foi possível excluir o projeto reportado (há dados vinculados).';
            break;
        case 'USUARIO':
            $ok = usuario_excluir((int)$rep['id_reportado']);
            $usuarioRemovido = $ok;
            $message = $ok ? 'Usuário reportado excluído.' : 'Não foi possível excluir o usuário reportado (há dados vinculados).';
            break;
        case 'COMENTARIO':
            $ok = comentario_excluir((int)$rep['id_reportado']);
            $message = $ok ? 'Comentário reportado excluído.' : 'Não foi possível excluir o comentário reportado.';
            break;
        default:
            $message = 'Reportagem removida.';
    }

    if ($ok) {
        reportagem_ignorar($id); // some com a reportagem em si também
    }

    return ['success' => $ok, 'message' => $message, 'usuario_removido' => $usuarioRemovido];
}

/* ---------------------------------------------------------------------
 * Flash message (mensagens de confirmação entre páginas)
 * ------------------------------------------------------------------- */

/** Total de reportagens pendentes no sistema (usado no selo do avatar no topo). */
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
