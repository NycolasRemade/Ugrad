-- tipos: usuário, professor, empresário, instituição, administrador
CREATE TABLE tipos_usuario(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(14) NOT NULL
);

CREATE TABLE usuarios(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(64),
  email varchar(128) unique,
  senha varchar(128),
  descricao varchar(500),
  tipo int NOT NULL,
  ativada boolean,
  imagem_perfil longblob,
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tipo) REFERENCES tipos_usuario (id)
);

CREATE TABLE turmas(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL,
  instituicao int NOT NULL,
  FOREIGN KEY (instituicao) REFERENCES usuarios(id)
);

CREATE TABLE extra_usuarios(
  id int PRIMARY KEY NOT NULL,
  FOREIGN KEY (id) REFERENCES usuarios (id),
  turma int,
  professor int,
  instituicao int,
  FOREIGN KEY (instituicao) REFERENCES usuarios(id),
  FOREIGN KEY (professor) REFERENCES usuarios(id),
  FOREIGN KEY (turma) REFERENCES turmas(id)
);

CREATE TABLE categorias(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25)
);

CREATE TABLE proj_estado(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25)
);


CREATE TABLE projetos(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL,
  estado int NOT NULL,
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estado) REFERENCES proj_estado(id),
  img longblob
);

CREATE TABLE proj_dados(
  id int PRIMARY KEY NOT NULL,
  FOREIGN KEY (id) REFERENCES projetos(id),
  descricao text,
  historia text
);

CREATE TABLE proj_categorias(
  id_projeto int NOT NULL,
  id_categoria int NOT NULL,
  PRIMARY KEY (id_projeto, id_categoria),
  FOREIGN KEY (id_projeto) REFERENCES projetos(id),
  FOREIGN KEY (id_categoria) REFERENCES categorias(id)
);

CREATE TABLE proj_imagens(
  id int PRIMARY KEY AUTO_INCREMENT,
  id_projeto int NOT NULL,
  FOREIGN KEY (id_projeto) REFERENCES projetos(id),
  img longblob
);

CREATE TABLE comentarios(
  id int PRIMARY KEY AUTO_INCREMENT,
  id_usuario int NOT NULL,
  id_projeto int NOT NULL,
  feedback boolean NOT NULL,
  comentario varchar(1000),
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
  FOREIGN KEY (id_projeto) REFERENCES projetos(id)
);

CREATE TABLE status_convites(
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome varchar(20) NOT NULL
);


CREATE TABLE convites(
  id int PRIMARY KEY AUTO_INCREMENT,
  id_convidante int NOT NULL,
  id_convidado int NOT NULL,
  status_convite int NOT NULL,
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_convidante) REFERENCES usuarios (id),
  FOREIGN KEY (id_convidado) REFERENCES usuarios (id),
  FOREIGN KEY (status_convite) REFERENCES status_convites (id)
);

CREATE TABLE tipo_rep(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL
);

CREATE TABLE tabela_rep(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL
);

CREATE TABLE reportagens(
  id int PRIMARY KEY AUTO_INCREMENT,
  id_usuario int NOT NULL,
  id_reportado int NOT NULL,
  tipo_rep int NOT NULL,
  tabela_rep int NOT NULL,
  data_reportagem timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
  FOREIGN KEY (tipo_rep) REFERENCES tipo_rep(id),
  FOREIGN KEY (tabela_rep) REFERENCES tabela_rep(id)
);


