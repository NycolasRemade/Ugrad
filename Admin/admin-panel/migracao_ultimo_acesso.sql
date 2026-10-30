-- OPCIONAL — rode uma vez no banco "ugrad" se quiser as estatísticas
-- "Logaram na última hora" e "Logaram nas últimas 24h" no dashboard.
--
-- O que faz: cria uma coluna nova que guarda quando a conta logou pela
-- última vez. Não mexe em nenhuma coluna ou dado existente.
--
-- Depois de rodar, o login do site principal precisa chamar uma linha logo
-- após autenticar o usuário pra o horário ser gravado:
--
--     require_once 'admin-panel/includes/data.php';  // ajuste o caminho
--     registrar_acesso($idDoUsuarioLogado);
--
-- Sem rodar este arquivo, os dois cards simplesmente não aparecem.

ALTER TABLE usuarios
  ADD COLUMN ultimo_acesso timestamp NULL DEFAULT NULL;
