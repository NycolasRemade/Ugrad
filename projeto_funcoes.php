<script>

        function mudarAba(nomeAba) {
            document.getElementById('aba-visao-geral').style.display = 'none';
            document.getElementById('aba-historia').style.display = 'none';
            document.getElementById('aba-avaliacoes').style.display = 'none';

            document.getElementById('btn_visao-geral').className = 'deepmod';
            document.getElementById('btn_historia').className = 'deepmod';
            document.getElementById('btn_avaliacoes').className = 'deepmod';

            const bloco = document.getElementById('aba-' + nomeAba);
            const botao = document.getElementById('btn_' + nomeAba);
            
            if (bloco) {
                bloco.style.display = 'block';
                location.hash = nomeAba;
                if (botao) botao.className = 'btn-novo_mod';
            } else {
                document.getElementById('aba-visao-geral').style.display = 'block';
                location.hash = 'visao-geral';
            }
        }

        if (location.hash && location.hash !== '#') mudarAba(location.hash.slice(1));
        else mudarAba('visao-geral');

        function toggleFormAvaliacao() {
            const formContainer = document.getElementById('form-avaliacao-container');
            formContainer.style.display = (formContainer.style.display === 'none') ? 'block' : 'none';
        }

        function definirNota(valor) {
            document.getElementById('nota_input').value = valor;
            const estrelas = document.querySelectorAll('#estrelas-rating span');
            estrelas.forEach((estrela, index) => {
                estrela.textContent = (index < valor) ? '★' : '☆';
            });
        }

        function mostrarSeletor(tipo) {
            const selectorDiv = document.getElementById(tipo + ' selector');
            selectorDiv.style.display = (selectorDiv.style.display === 'none') ? 'block' : 'none';
        }

        async function confirmarSelecao(tipo) {
            if (tipo === 'membros') {
                const select = document.getElementById('select membro');
                const value = select.value;
                if (!value) return;

                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('convidar_membro_id', value);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();

                    if (res.success) {
                        const container = document.getElementById('membros-selecionados');
                        const itemDiv = document.createElement('div');
                        itemDiv.id = 'membros-item-' + res.id;
                        itemDiv.innerHTML = `
                            <span>${res.nome}</span>
                            <button type="button" onclick="removerMembroAJAX(${res.id})" class="btn-x">x</button>
                        `;
                        container.appendChild(itemDiv);

                        const optToRem = select.querySelector(`option[value="${value}"]`);
                        if (optToRem) optToRem.remove();

                        select.selectedIndex = 0;
                        document.getElementById('membros selector').style.display = 'none';
                    }
                } catch (err) {
                    console.error('Erro ao convidar membro:', err);
                }
                return;
            }

            const select = document.getElementById('select categoria');
            const value = select.value;
            const text = select.options[select.selectedIndex].text;

            if (!value) return;

            if (document.getElementById(tipo + '-item-' + value)) {
                select.selectedIndex = 0;
                document.getElementById(tipo + ' selector').style.display = 'none';
                return;
            }

            const container = document.getElementById(tipo + '-selecionados');
            const itemDiv = document.createElement('div');
            itemDiv.id = tipo + '-item-' + value;
            itemDiv.innerHTML = `
                <span>${text}</span>
                <input type="hidden" name="${tipo}[]" value="${value}">
                <button type="button" onclick="removerItem('${tipo}-item-${value}')" class="btn-x">x</button>
            `;

            container.appendChild(itemDiv);
            select.selectedIndex = 0;
            document.getElementById(tipo + ' selector').style.display = 'none';
        }

        function removerItem(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.remove();
            }
        }

        function toggleFiltros() {
            const painel = document.getElementById('painel-filtros');
            painel.style.display = (painel.style.display === 'none') ? 'block' : 'none';
        }

        function filtrarComentarios() {
            const tipo = document.getElementById('filtro_tipo').value.toLowerCase();
            const nota = document.getElementById('filtro_nota').value;
            const busca = document.getElementById('filtro_busca').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.card-comentario');

            cards.forEach(card => {
                const cardTipo = card.getAttribute('data-tipo') || '';
                const cardNota = card.getAttribute('data-nota') || '';
                const cardTexto = card.getAttribute('data-texto') || '';

                let atendeTipo = false;
                if (tipo === 'todos') {
                    atendeTipo = true;
                } else if (tipo === 'professor' && cardTipo.includes('professor')) {
                    atendeTipo = true;
                } else if (tipo === 'empresario' && (cardTipo.includes('empresario') || cardTipo.includes('investidor'))) {
                    atendeTipo = true;
                }

                let atendeNota = false;
                if (nota === 'todas' || cardNota === nota) {
                    atendeNota = true;
                }

                const atendeBusca = !busca || cardTexto.includes(busca);

                if (atendeTipo && atendeNota && atendeBusca) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>