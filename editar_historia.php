<script src='modular_text.js'></script>
</div>
    <div id="aba-historia">
        <form method="POST" action="">
            <input id="salvar_historia" type="hidden" name="salvar_historia" value="1">

            <div id='historia_bloco_inicial'>
                <div>
                    <input type="file" id="input_imagem_historia" accept="image/png, image/jpeg, image/webp" hidden>
                    <button type="button" class="btn-novo" onclick="document.getElementById('input_imagem_historia').click()">
                        + Upload de imagem
                    </button>
                    <span id="status-upload-historia"></span>
                </div>
                <textarea name="historia_projeto" id="historia_projeto" 
                style="max-width: 100%; border: 1px solid black;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>

                <div class='buttons_criar'>
                    <button type="submit" class="btn-novo">Salvar</button>
                </div>
            </div>
            <br>
            <br>
        </form>

        <!-- GALERIA DE IMAGENS DA HISTÓRIA -->
        <div id="secao-imagens-historia" style="margin-top: 20px;">
            <h4>Imagens enviadas para a história</h4>
            <div id="galeria-imagens-historia" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px;">
                <?php if (!empty($imagens_historia)): ?>
                    <?php foreach ($imagens_historia as $img_item): ?>
                        <?php 
                            $id_img = (int)$img_item['id'];
                            $url_img = 'projeto_img.php?id=' . $id_img; 
                        ?>
                        <div class="card-imagem-historia" data-id="<?= $id_img ?>" style="padding: 8px; width: 160px; background: #fafafa; border: 1px solid #ccc; border-radius: 6px; display: flex; flex-direction: column; gap: 6px;">
                            <div class="historia-imagem" style="background-image: url(<?= htmlspecialchars($url_img) ?>); width: 100%; height: 100px; background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                            <button type="button" class="btn-novo" style="width: 100%;" onclick="inserirImagemNoTexto('<?= htmlspecialchars($url_img) ?>')">
                                Adicionar ao texto
                            </button>
                            <button type="button" class="btn-novo" style="width: 100%; background-color: #C50000; color: white;" onclick="excluirImagemHistoria(<?= $id_img ?>, this)">
                                Excluir
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="msg-sem-imagens-historia" style="color: #666; font-size: 0.9em;">Nenhuma imagem enviada ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <hr style="margin: 20px 0;">

        <div id="saida-historia-projeto">
            <script>
                const divSaida = document.getElementById('saida-historia-projeto');
                divSaida.replaceChildren(parser(lexer(
                    <?= json_encode($projeto['historia'] ?? ''); ?>
                .replace(/\r/g, ""))));
            </script>
        </div>

        <script>
            function atualizarPreviewHistoria() {
                const textarea = document.getElementById('historia_projeto');
                const divSaida = document.getElementById('saida-historia-projeto');
                if (divSaida && typeof parser === 'function' && typeof lexer === 'function') {
                    divSaida.replaceChildren(parser(lexer(textarea.value.replace(/\r/g, ""))));
                }
            }

            function inserirImagemNoTexto(url) {
                const textarea = document.getElementById('historia_projeto');
                const tag = `<#img "${url}"#>`;
                const inicio = textarea.selectionStart;
                const fim = textarea.selectionEnd;
                const textoAtual = textarea.value;

                const antes = textoAtual.substring(0, inicio);
                const depois = textoAtual.substring(fim);
                const quebraAntes = (antes.length > 0 && !antes.endsWith('\n')) ? '\n' : '';
                const quebraDepois = (depois.length > 0 && !depois.startsWith('\n')) ? '\n' : '';

                textarea.value = antes + quebraAntes + tag + quebraDepois + depois;

                const novaPosicao = (antes + quebraAntes + tag + quebraDepois).length;
                textarea.focus();
                textarea.setSelectionRange(novaPosicao, novaPosicao);

                atualizarPreviewHistoria();
            }

            function adicionarCardImagemGaleria(id, url) {
                const msgVazia = document.getElementById('msg-sem-imagens-historia');
                if (msgVazia) msgVazia.remove();

                const galeria = document.getElementById('galeria-imagens-historia');
                const card = document.createElement('div');
                card.className = 'card-imagem-historia';
                card.dataset.id = id;
                card.style.cssText = 'padding: 8px; width: 160px; background: #fafafa; border: 1px solid #ccc; border-radius: 6px; display: flex; flex-direction: column; gap: 6px;';

                const imgDiv = document.createElement('div');
                imgDiv.className = 'historia-imagem';
                imgDiv.style.cssText = `background-image: url(${url}); width: 100%; height: 100px; background-size: contain; background-repeat: no-repeat; background-position: center;`;

                const btnAdicionar = document.createElement('button');
                btnAdicionar.type = 'button';
                btnAdicionar.className = 'btn-novo';
                btnAdicionar.style.width = '100%';
                btnAdicionar.textContent = 'Adicionar ao texto';
                btnAdicionar.onclick = () => inserirImagemNoTexto(url);

                const btnExcluir = document.createElement('button');
                btnExcluir.type = 'button';
                btnExcluir.className = 'btn-novo';
                btnExcluir.style.cssText = 'width: 100%; background-color: #C50000; color: white;';
                btnExcluir.textContent = 'Excluir';
                btnExcluir.onclick = () => excluirImagemHistoria(id, btnExcluir);

                card.appendChild(imgDiv);
                card.appendChild(btnAdicionar);
                card.appendChild(btnExcluir);
                galeria.appendChild(card);
            }

            async function excluirImagemHistoria(id, botao) {
                if (!confirm('Deseja realmente excluir esta imagem?')) return;

                const statusEl = document.getElementById('status-upload-historia');
                botao.disabled = true;

                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('excluir_imagem_historia_id', id);

                try {
                    const resposta = await fetch('editar_projeto.php?id=<?= (int)$id_projeto ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const dados = await resposta.json();

                    if (dados.success) {
                        // Remove o card da galeria
                        const card = botao.closest('.card-imagem-historia');
                        if (card) card.remove();

                        // Exibe mensagem caso a galeria fique vazia
                        const galeria = document.getElementById('galeria-imagens-historia');
                        if (galeria.querySelectorAll('.card-imagem-historia').length === 0) {
                            const msg = document.createElement('p');
                            msg.id = 'msg-sem-imagens-historia';
                            msg.style.cssText = 'color: #666; font-size: 0.9em;';
                            msg.textContent = 'Nenhuma imagem enviada ainda.';
                            galeria.appendChild(msg);
                        }

                        // Remove referências a essa imagem no textarea e atualiza a pré-visualização
                        const textarea = document.getElementById('historia_projeto');
                        const regexTag = new RegExp(`\\n?<#img\\s+"projeto_img\\.php\\?id=${id}"#>\\n?`, 'g');
                        textarea.value = textarea.value.replace(regexTag, '\n').trim();
                        atualizarPreviewHistoria();

                        statusEl.textContent = 'Imagem excluída!';
                        statusEl.style.color = 'green';
                    } else {
                        botao.disabled = false;
                        statusEl.textContent = dados.message || 'Erro ao excluir imagem.';
                        statusEl.style.color = 'red';
                    }
                } catch (erro) {
                    botao.disabled = false;
                    statusEl.textContent = 'Falha na comunicação com o servidor.';
                    statusEl.style.color = 'red';
                }
            }

            document.getElementById('input_imagem_historia').addEventListener('change', async function () {
                const arquivo = this.files[0];
                if (!arquivo) return;

                const statusEl = document.getElementById('status-upload-historia');
                statusEl.textContent = 'Enviando imagem...';
                statusEl.style.color = '#555';

                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('imagem_historia', arquivo);

                try {
                    const resposta = await fetch('editar_projeto.php?id=<?= (int)$id_projeto ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const dados = await resposta.json();

                    if (dados.success) {
                        statusEl.textContent = 'Imagem adicionada!';
                        statusEl.style.color = 'green';
                        adicionarCardImagemGaleria(dados.id, dados.url);
                        inserirImagemNoTexto(dados.url);
                    } else {
                        statusEl.textContent = dados.message || 'Erro ao enviar imagem.';
                        statusEl.style.color = 'red';
                    }
                } catch (erro) {
                    statusEl.textContent = 'Falha na comunicação com o servidor.';
                    statusEl.style.color = 'red';
                } finally {
                    this.value = '';
                }
            });
        </script>
    </div>

<div>