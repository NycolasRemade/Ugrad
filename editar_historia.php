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
            </div>
            <br>
            <br>

            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar</button>
            </div>
        </form>

        <!-- GALERIA DE IMAGENS DA HISTÓRIA -->
        <div id="secao-imagens-historia" style="margin-top: 20px;">
            <h4>Imagens enviadas para a história</h4>
            <div id="galeria-imagens-historia" style="display: flex; flex-wrap: wrap;">
                <?php if (!empty($imagens_historia)): ?>
                    <?php foreach ($imagens_historia as $img_item): ?>
                        <?php $url_img = 'projeto_img.php?id=' . (int)$img_item['id']; ?>
                        <div class="card-imagem-historia" style="padding: 8px; width: 160px; height: 100px; background: #fafafa;">
                            <div class="historia-imagem" style="background-image: url(<?= htmlspecialchars($url_img) ?>); width: 100%; height: 100%; background-fit: contain;"></div>
                            <button type="button" class="btn-novo" onclick="inserirImagemNoTexto('<?= htmlspecialchars($url_img) ?>')">
                                Adicionar ao texto
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

                // Atualiza a pré-visualização imediatamente
                const divSaida = document.getElementById('saida-historia-projeto');
                if (divSaida && typeof parser === 'function' && typeof lexer === 'function') {
                    divSaida.replaceChildren(parser(lexer(textarea.value.replace(/\r/g, ""))));
                }
            }

            function adicionarCardImagemGaleria(url) {
                const msgVazia = document.getElementById('msg-sem-imagens-historia');
                if (msgVazia) msgVazia.remove();

                const galeria = document.getElementById('galeria-imagens-historia');
                const card = document.createElement('div');
                card.className = 'card-imagem-historia';
                card.style.cssText = 'border: 1px solid #ccc; border-radius: 6px; padding: 8px; width: 150px; display: flex; flex-direction: column; align-items: center; gap: 8px; background: #fafafa;';

                const img = document.createElement('img');
                img.src = url;
                img.alt = 'Imagem da história';
                img.style.cssText = 'width: 100%; height: 100px; object-fit: cover; border-radius: 4px;';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-novo';
                btn.style.cssText = 'width: 100%; font-size: 0.85em;';
                btn.textContent = 'Adicionar ao texto';
                btn.onclick = () => inserirImagemNoTexto(url);

                card.appendChild(img);
                card.appendChild(btn);
                galeria.appendChild(card);
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
                        adicionarCardImagemGaleria(dados.url);
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