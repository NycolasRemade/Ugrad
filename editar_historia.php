    <script src='modular_text.js'></script>
</div>
    <div id="aba-historia">
        <form method="POST" action="">
            <input id="salvar_historia" type="hidden" name="salvar_historia" value="1">

            <div id='historia_bloco_inicial'>
                <textarea name="historia_projeto" id="historia_projeto" 
                style="max-width: 100%; border: 1px solid black;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>
            </div>
            <br>
            <br>

            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar</button>
            </div>
        </form>

            <div id="saida-historia-projeto">
                <script>
                    const divSaida = document.getElementById('saida-historia-projeto');
                    divSaida.replaceChildren(parser(lexer(
                        <?= json_encode($projeto['historia']); ?>
                    .replace(/\r/g, ""))));
                </script>
            </div>

    </div>

<div>