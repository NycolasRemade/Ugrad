


<div id="aba-avaliacoes" style="display: none; min-width: 640px;">
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
            
            <button type="button" onclick="toggleFiltros()" class="btn-novo">Filtros +</button>
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
                </div><br>
                <div>
                    <label for="filtro_busca"><strong>Pesquisar palavra-chave:</strong></label>
                    <input type="text" id="filtro_busca" onkeyup="filtrarComentarios()" placeholder="Digite para filtrar..." style="padding: 4px 8px; font-size:24px;">
                </div>
            </div>
        </div>

        <br>

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
                <p>Nenhuma avaliação para este projeto ainda.</p>
            <?php endif; ?>
        </main>
    </div>