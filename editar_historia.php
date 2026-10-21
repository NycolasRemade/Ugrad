


<div id="aba-historia" style="display: none; min-width: 640px;">

        <h2>História</h2>
        <form method="POST" action="">
            <input type="hidden" name="salvar_historia" value="1">

            <textarea name="historia_projeto" id="historia_projeto" 
            style="max-width: 100%; border: 1px solid black;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>

            <br>
            <br>

            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar Alterações</button>
            </div>
        </form>
</div>
    <script src='modular_text.js'></script>