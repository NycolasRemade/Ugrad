CREATE DATABASE ugrad;
USE ugrad;

-- tipos: aluno, professor, empresário, instituição, administrador
CREATE TABLE tipos_usuario(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(14) NOT NULL
);
INSERT INTO tipos_usuario (nome) VALUES 
  ('ALUNO'),
  ('PROFESSOR'),
  ('EMPRESARIO'),
  ('INSTITUICAO'),
  ('ADMINISTRADOR');


CREATE TABLE usuarios(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(64),
  email varchar(128) unique NOT NULL,
  senha varchar(128) NOT NULL,
  descricao varchar(500),
  tipo int NOT NULL,
  ativada boolean DEFAULT TRUE,
  imagem_perfil longblob,
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tipo) REFERENCES tipos_usuario (id)
);

CREATE TABLE turmas(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL,
  id_instituicao int NOT NULL,
  FOREIGN KEY (id_instituicao) REFERENCES usuarios(id)
);

CREATE TABLE extra_usuarios(
  id_usuario int PRIMARY KEY NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
  id_turma int,
  id_instituicao int,
  FOREIGN KEY (id_turma) REFERENCES turmas(id),
  FOREIGN KEY (id_instituicao) REFERENCES usuarios(id)
);

CREATE TABLE codigo_instituicao(
  id_instituicao int NOT NULL,
  codigo varchar(25) NOT NULL,
  tipo_usuario int NOT NULL,
  FOREIGN KEY(id_instituicao) REFERENCES usuarios(id),
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25)
);

CREATE TABLE proj_estado(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25)
);
INSERT INTO proj_estado (nome) VALUES
  ('PRIVADO_PRIVADO'),
  ('PUBLICO_PRIVADO'),
  ('PUBLICO_PUBLICO');


CREATE TABLE projetos(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL,
  estado int NOT NULL,
  data_criacao timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estado) REFERENCES proj_estado(id),
  img longblob
);

CREATE TABLE proj_dados(
  id_projeto int PRIMARY KEY NOT NULL,
  FOREIGN KEY (id_projeto) REFERENCES projetos(id),
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

CREATE TABLE proj_membros_status(
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome varchar(20) NOT NULL
);
INSERT INTO proj_membros_status (nome) VALUES
  ('DONO'),
  ('MEMBRO'),
  ('CONVITE_PENDENTE');

CREATE TABLE proj_membros(
  id int PRIMARY KEY AUTO_INCREMENT,
  id_convidante int NOT NULL,
  id_convidado int NOT NULL,
  id_projeto int NOT NULL,
  status_membro int NOT NULL,
  data_convite timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_convidante) REFERENCES usuarios (id),
  FOREIGN KEY (id_convidado) REFERENCES usuarios (id),
  FOREIGN KEY (id_projeto) REFERENCES projetos (id),
  FOREIGN KEY (status_membro) REFERENCES proj_membros_status (id)
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

CREATE TABLE tipo_rep(
  id int PRIMARY KEY AUTO_INCREMENT,
  nome varchar(25) NOT NULL
);
INSERT INTO tipo_rep (nome) VALUES
  ('PROJETO'),
  ('USUARIO'),
  ('COMENTARIO'),
  ('GENERICO');

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



-- CÓDIGO PARA TESTES -------------------------------------------------------------------

INSERT INTO usuarios (nome, email, senha, descricao, tipo) VALUES
  ('admin', 'admin@ugrad.com', '$2y$10$x4auNVaTZIoAENyj9Xsdc.cQoXdCJswNjtPeJfAi155iXAUNjH3by', 'conta de teste', 5),
  -- senha: admin
  ('instituição', 'instituição@ugrad.com', '$2y$10$cMmmO0Q30iL8Xd.MSfnQieiEfjcIVN6PTkfI0pHxbT3KuqFC.Ly3W', 'conta de teste', 4);
  -- senha: instituição

INSERT INTO codigo_instituicao (id_instituicao, codigo, tipo_usuario) VALUES
  (1, 'abcdefgh', 1);
