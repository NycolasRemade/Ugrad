<?php
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';

$id = (int)($_GET['id'] ?? 0);
$projeto = find_projeto($id);
$isPartial = ($_GET['partial'] ?? '') === '1'; // pedido via AJAX pela tela de pesquisa
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (!$projeto) {
    http_response_code(404);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado.']);
        exit;
    }
    if ($isPartial) {
        echo '<p class="detail-placeholder">Projeto não encontrado.</p>';
        exit;
    }
    $pageTitle = 'Projeto não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<p>Projeto não encontrado. <a href="pesquisa.php">Voltar para a pesquisa</a>.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'excluir':
            if (projeto_excluir($id)) {
                $result['message'] = 'Projeto ' . $projeto['nome'] . ' excluído.';
                $result['removed'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Não foi possível excluir o projeto "' . $projeto['nome'] . '": há dados vinculados que não puderam ser removidos.';
            }
            break;
        default:
            $result['success'] = false;
            $result['message'] = 'Ação desconhecida.';
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Fallback sem JS: navegação normal com mensagem via sessão.
    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'pesquisa.php?tab=projetos' : ('projeto.php?id=' . $id)));
    exit;
}

$categorias = categorias_do_projeto($id);
$membros = membros_do_projeto($id);
$reportagens = reportagens_do_projeto($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/projeto_detail.php';
    exit;
}

        <h2 id='hist'>História</h2>

        <!-- VISÃO GERAL -->
        <div id="aba-visao-geral">
            <h1><?= htmlspecialchars($projeto['nome'] ?? 'Nome do projeto') ?></h1>

            <?php if ($pode_editar_projeto): ?>
            <br>
            <div class='buttons_criar' style="float: left;">
                <a href="editar_projeto.php?id=<?= $id_projeto; ?>#visao-geral" class="btn-novo">Editar</a>
            </div><br><br>
            <?php endif; ?>

            <div>
                <br>
                <?php if (!empty($projeto['img'])): ?>
                    <div style="width: 640px; height:360px; background-position:center; background-repeat:none; background-size:cover; background-image: url('data:image/webp;base64,<?= base64_encode($projeto['img']) ?>');"></div>
                <?php else: ?>
                    <div style="width: 640px; height:360px; background-color: lightgray; display: flex; justify-content: center; align-items: center;">
                        [ Imagem do Projeto ]
                    </div>
                <?php endif; ?>
            </div>

            <p>
                <br>
                <strong>Criado por:</strong> 
                <?= !empty($membros) ? htmlspecialchars(implode(', ', $membros)) : 'Nenhum integrante cadastrado' ?>
            </p>

            <p>
                <strong>Tags:</strong> 
                <?= !empty($tags) ? htmlspecialchars(implode(', ', $tags)) : 'Sem categorias' ?>
            </p>

            <div>
                <br>
                <p><?= nl2br(htmlspecialchars($projeto['descricao'] ?? 'Sem descrição disponível.')) ?></p>
            </div>
        </div>

        <!-- AVALIAÇÕES -->
        <div id="aba-avaliacoes" style="display: none;">
            <div>
                <h2>Avaliações <?php 
                    $total_comentarios = count($comentarios);
                    if ($total_comentarios > 0) {
                        $soma_notas = array_sum(array_column($comentarios, 'nota'));
                        $media_nota = (int) round($soma_notas / $total_comentarios * 2) / 2;
                        $estrelas_cheias = (int) floor($media_nota);
                        if ($estrelas_cheias === $media_nota) {
                            echo str_repeat('★', $estrelas_cheias) . str_repeat('☆', 5 - $estrelas_cheias);
                        } else {
                            echo str_repeat('★', $estrelas_cheias) . '⯪' . str_repeat('☆', 4 - $estrelas_cheias);
                        }
                    } else {
                        echo '☆☆☆☆☆';
                    }
                ?></h2>
                <button type="button" class="btn-novo" onclick="toggleFiltros()">Filtros +</button>
            </div>

            <!-- PAINEL DE FILTROS -->
            <div id="painel-filtros" class="selector selector-mini" style="display: none; margin: 15px 0; padding: 12px; background-color: #f9f9f9; border: 1px solid #ddd;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div>
                        <label for="filtro_tipo"><strong>Exibir:</strong></label>
                        <select id="filtro_tipo" onchange="filtrarComentarios()">
                            <option value="todos">Todos os tipos</option>
                            <option value="professor">Apenas professores</option>
                            <option value="empresario">Apenas investidores</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro_nota"><strong>Avaliação:</strong></label>
                        <select id="filtro_nota" onchange="filtrarComentarios()">
                            <option value="todas">Todas as notas</option>
                            <option value="5">5 ★★★★★</option>
                            <option value="4">4 ★★★★☆</option>
                            <option value="3">3 ★★★☆☆</option>
                            <option value="2">2 ★★☆☆☆</option>
                            <option value="1">1 ★☆☆☆☆</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro_busca"><strong>Pesquisar palavra-chave:</strong></label>
                        <input type="text" id="filtro_busca" onkeyup="filtrarComentarios()" placeholder="Digite para filtrar..." style="padding: 4px 8px;">
                    </div>
                </div>
            </div>

            <br>

            <div id="botao-toggle-avaliacao">
                <button type="button" onclick="toggleFormAvaliacao()"  class="btn-novo"><?= (empty($comentario_usuario)) ? 'Deixe sua avaliação +' : 'Editar avaliação' ?></button>
                <br><br>
            </div>

            <!-- ENTRADA DE TEXTO E FORMULÁRIO DE AVALIAÇÃO -->
            <div id="form-avaliacao-container" style="display: none;">
                <button type="button" onclick="toggleFormAvaliacao()" class="btn-novo btn-secundario">Cancelar x</button>
                <br><br>
                <form action="" method="POST">
                    <input type="hidden" name="id_comentario" value="<?= (empty($comentario_usuario)) ? '0' : $comentario_usuario['id'] ?>">
                    <div>
                        <textarea name="comentario" placeholder="Escreva uma avaliação..." rows="4" required><?php if (!empty($comentario_usuario)) { echo $comentario_usuario['comentario']; }?></textarea>
                    </div>
                    <br>
                    <div>
                    <?php if (empty($comentario_usuario)): ?>
                        <input type="hidden" name="nota" id="nota_input" value="5">
                        <span id="estrelas-rating" style="cursor: pointer; font-size: 1.3rem;">
                            <span onclick="definirNota(1)">★</span><span onclick="definirNota(2)">★</span><span onclick="definirNota(3)">★</span><span onclick="definirNota(4)">★</span><span onclick="definirNota(5)">★</span>
                        </span>
                    <?php else: ?>
                        <input type="hidden" name="nota" id="nota_input" value="<?= $comentario_usuario['nota'] ?>">
                        <span id="estrelas-rating" style="cursor: pointer; font-size: 1.3rem;">
                            <?php $nota = (int)$comentario_usuario['nota'];
                            for ($i = 1; $i <= $nota; $i++): ?><span onclick="definirNota(<?= $i ?>)">★</span><?php endfor;
                            for ($i = $nota + 1; $i <= 5; $i++): ?><span onclick="definirNota(<?= $i ?>)">☆</span><?php endfor; ?>
                        </span>
                    <?php endif; ?>
                        <button type="submit" class="btn-novo">Enviar</button>
                    </div>
                </form>
            </div>

            <main id="lista-comentarios">
                <?php if (!empty($comentarios)): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="card-comentario" 
                            data-tipo="<?= htmlspecialchars(mb_strtolower($c['tipo_usuario'])) ?>" 
                            data-nota="<?= (int)$c['nota'] ?>" 
                            data-texto="<?= htmlspecialchars(mb_strtolower($c['comentario'] . ' ' . $c['nome_usuario'])) ?>">
                            <div>
                                <div class="projeto-membros">
                                    <div class="membro-avatar" style="background-image: url('data:image/webp;base64,<?= base64_encode($c['imagem_perfil']) ?>')" title="<?= htmlspecialchars($c['nome_usuario']); ?>"></div>
                                </div>
                                    <strong><?= htmlspecialchars($c['nome_usuario']) ?> (<?= htmlspecialchars(ucfirst(strtolower($c['tipo_usuario']))) ?>)</strong>
                                <span><?= str_pad(str_repeat('★', $c['nota']), 15, '☆') ?></span>
                            </div>
                            
                            <p><?= htmlspecialchars($c['comentario']) ?></p>
                            
                            <div>
                                <small>Data: <?= date('d/m/Y H:i', strtotime($c['data_criacao'])) ?></small>
                                <?php if ($c['data_criacao'] !== $c['data_edicao']): ?>
                                <br><small>Editada em: <?= date('d/m/Y H:i', strtotime($c['data_edicao'])) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhuma avaliação cadastrada para este projeto ainda.</p>
                <?php endif; ?>
            </main>
        </div>

        <!-- HISTÓRIA -->
        <script src='modular_text.js'></script>

        <div id="aba-historia" style="display: none;">

            <?php if ($pode_editar_projeto): ?>
            <div class='buttons_criar' style="float: left;">
                <a href="editar_projeto.php?id=<?= $id_projeto; ?>#historia" class="btn-novo">Editar</a>
            </div><br><br>
            <?php endif; ?>

        </div>
    </div>

    <div id="saida-historia-projeto" style='width:77%'>
        <script>
            const divSaida = document.getElementById('saida-historia-projeto');
            divSaida.replaceChildren(parser(lexer(
                <?= json_encode($projeto['historia']); ?>
            .replace(/\r/g, ""))));
        </script>
    </div>
</div>

    <?php include 'projeto_funcoes.php'; ?>

</body>
</html>
