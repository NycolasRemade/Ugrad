    <script src='modular_text.js'></script>

    <div id="aba-historia">
        <form method="POST" action="">
            <input id="salvar_historia" type="hidden" name="salvar_historia" value="1">

            <textarea name="historia_projeto" id="historia_projeto" 
            style="max-width: 100%; border: 1px solid black;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>

            <br>
            <br>

            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar</button>
                <button id="salvar_e_visualizar" type="submit" class="btn-novo btn-secundario">Salvar e visualizar</button>
                <script>
                    document.getElementById("salvar_e_visualizar").onclick = () => {
                        document.getElementById("salvar_historia").value = "2";
                    };
                </script>
            </div>
        </form>
    </div>