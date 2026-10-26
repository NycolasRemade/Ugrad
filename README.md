# Ugrad
O Ugrad é um projeto de

para ativar a extensão GD no php.ini copie e cole esse código no powershell: "100%" "sem vírus"* "atualizado" "functionando gratuito" "nuketown OOB unpatched 2026" "*New*"
`powershell -Command "(Get-Content C:\xampp\php\php.ini) -replace '^;extension=gd', 'extension=gd' | Set-Content C:\xampp\php\php.ini"`

pra roda o cocker do davi
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/davi_polheim.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

pra roda o cocker do nycolas
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/nycolas_pierobon.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

pra roda o cocker do outro davi que não é davi
`docker run --name ugrad -e MYSQL_ROOT_PASSWORD=ugrad -e MYSQL_DATABASE=ugrad -v /Users/davi_polheim.ADVENTISTAS~ --hostname ugrad -p 3306:3306 -d mysql`

o cocker não funciona em outras máquinas que não sejam do lab cocker, então utilize xampp ainda nas configurações antigas 

pra entrar no banco de dados (acho que falta coisa)
`docker exec -it ugrad -u root -p ugrad`