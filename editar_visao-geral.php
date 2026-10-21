



<div id="aba-visao-geral" style="min-width: 640px;">
        <form method="POST" action="">
            <input type="hidden" name="salvar_visao_geral" value="1">

            <div>
                <input type="text" id="nome_projeto" name="nome_projeto" value="<?= htmlspecialchars($projeto['nome'] ?? '') ?>" required>
            </div>
            <br>

            <!-- UPLOAD DA IMAGEM DO PROJETO -->
            <div id="area-clicavel-imagem" style="cursor: pointer; background-color:lightgray; background-position:center; background-repeat:none; background-size:cover; <?php if (!empty($projeto['img'])) echo 'background-image:url(\'data:image/webp;base64,' . base64_encode($projeto['img']) . '\');'; ?> display:flex; justify-content:center; align-items:center; width:640px; height:360px;">
                [ Clique para selecionar uma imagem ]
            </div>
            <script>
                document.getElementById("area-clicavel-imagem").onclick = function() {
                    document.getElementById("imagem_projeto").click();
                };
                const imgInput = document.getElementById("imagem_projeto");
                imgInput.onchange = function() {
                    if (imgInput.files.length > 0) {
                        document.getElementById('form-upload-imagem').submit();
                    }
                };
            </script>
            <br>

            <!-- MEMBROS -->
            <div>
                <strong>Membros:</strong>
                <div class="multiple_inline_mod">
                    <div id="membros-ativos-lista">
                        <?php foreach ($membros_ativos as $ma): ?>
                            <div id="membro-ativo-<?= $ma['id'] ?>">
                                <span><?= htmlspecialchars($ma['nome'] ?: $ma['email']) ?></span>
                                <?php if ($ma['id'] != $usuario_id && $ma['status_membro'] != 1): ?>
                                    <button type="button" onclick="removerMembroAJAX(<?= $ma['id'] ?>)" class="btn-x">x</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <script>
                async function removerMembroAJAX(idMembro) {
                    const formData = new FormData();
                    formData.append('ajax', '1');
                    formData.append('remover_membro_id', idMembro);

                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        const res = await response.json();

                        if (res.success) {
                            const elPendente = document.getElementById('membros-item-' + idMembro);
                            if (elPendente) elPendente.remove();

                            const elAtivo = document.getElementById('membro-ativo-' + idMembro);
                            if (elAtivo) elAtivo.remove();

                            const select = document.getElementById('select membro');
                            if (select && res.nome) {
                                const newOpt = document.createElement('option');
                                newOpt.value = idMembro;
                                newOpt.textContent = res.nome;
                                select.appendChild(newOpt);
                            }
                        }
                    } catch (err) {
                        console.error('Erro ao remover membro:', err);
                    }
                }
            </script>
            <br>

            <!-- CONVITES PENDENTES E NOVO CONVITE -->
            <div>
                <strong>Convites pendentes:</strong>
                <div class="multiple_inline_mod">
                    <div id="membros-selecionados" class="multiple_inline_mod">
                        <?php foreach ($membros_pendentes as $mp): ?>
                            <div id="membros-item-<?= $mp['id'] ?>">
                                <span><?= htmlspecialchars($mp['nome'] ?: $mp['email']) ?></span>
                                <button type="button" onclick="removerMembroAJAX(<?= $mp['id'] ?>)" class="btn-x">x</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="membros selector" class='selector' style="display: none;">
                        <select id="select membro" onchange="confirmarSelecao('membros')">
                            <option value="">Selecione um usuário para convidar...</option>
                            <?php 
                            $ids_existentes = array_column(array_merge($membros_ativos, $membros_pendentes), 'id');
                            foreach ($lista_usuarios as $u): 
                                if (in_array($u['id'], $ids_existentes)) continue;
                            ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome'] ?: $u['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" class="smallplus" onclick="mostrarSeletor('membros')">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="48" height="48" fill="#111111"/>
                            <path d="M22.7903 31.6827V24.9947H16.3903V22.8187H22.7903V16.1307H25.2223V22.8187H31.6223V24.9947H25.2223V31.6827H22.7903Z" fill="white"/>
                        </svg>
                    </button>
                </div>
            </div>
            <br>

            <div>
                <strong>Categorias:</strong>
                <div class="multiple_inline_mod">
                    <div id="categorias-selecionados">
                        <?php foreach ($categorias_projeto as $cp): ?>
                            <div id="categorias-item-<?= $cp['id'] ?>">
                                <span><?= htmlspecialchars($cp['nome']) ?></span>
                                <input type="hidden" name="categorias[]" value="<?= $cp['id'] ?>">
                                <button type="button" onclick="removerItem('categorias-item-<?= $cp['id'] ?>')" class="btn-x">x</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="categorias selector" class='selector' style="display: none;">
                        <select id="select categoria" onchange="confirmarSelecao('categorias')">
                            <option value="">Selecione uma categoria...</option>
                            <?php foreach ($lista_categorias as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" class="smallplus" onclick="mostrarSeletor('categorias')">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="48" height="48" fill="#111111"/>
                            <path d="M22.7903 31.6827V24.9947H16.3903V22.8187H22.7903V16.1307H25.2223V22.8187H31.6223V24.9947H25.2223V31.6827H22.7903Z" fill="white"/>
                        </svg>
                    </button>
                </div>
            </div>
            <br>

            <div>
                <label for="descricao"><strong>Descrição:</strong></label><br>
                <textarea id="descricao" name="descricao" rows="4" 
                style="width: 100%; max-width: 640px; border: 1px solid black;"><?= htmlspecialchars($projeto['descricao'] ?? '') ?></textarea>
            </div>

            <br>
            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar Alterações</button>
            </div>
        </form>
    </div>
