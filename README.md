# Ugrad
O Ugrad é um projeto de

para ativar a extensão GD no php.ini copie e cole esse código no powershell: "100%" "sem vírus"* "atualizado" "functionando gratuito" "nuketown OOB unpatched 2026" "*New*"
`powershell -Command "(Get-Content C:\xampp\php\php.ini) -replace '^;extension=gd', 'extension=gd' | Set-Content C:\xampp\php\php.ini"`

pra roda o docker do davi
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/davi_polheim.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

pra roda o docker do nycolas
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/nycolas_pierobon.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

pra roda o docker do outro davi que não é davi
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/davi_polheim.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

o docker não funciona em outras máquinas que não sejam do lab docker, então utilize xampp ainda nas configurações antigas 

pra entrar no banco de dados (acho que falta coisa)
`docker exec -it ugrad -u root -p ugrad`

O básico de nosso projeto é que existem instituições, onde existem salas, e é possível criar códigos de acesso que determinam se um usuário é do tipo professor ou aluno na
instituição, e também pode-se determinar de que sala o aluno é pelo código.
No cadastro de contas, o usuário coloca o código, que vincula sua conta à instituição, ou, se não colocar o código, 
se torna uma conta de empresário, que não consegue criar projetos, apenas visualizar os publicados e deixar comentários.
