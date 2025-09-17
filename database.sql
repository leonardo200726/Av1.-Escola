-- Criar o banco de dados
CREATE DATABASE IF NOT EXISTS escola;
USE escola;

-- Criar tabela de professores
CREATE TABLE IF NOT EXISTS professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    disciplina VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de alunos
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    idade INT NOT NULL,
    turma VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de notas
CREATE TABLE IF NOT EXISTS notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    professor_id INT NOT NULL,
    nota DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE
);

-- Criar tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    tipo ENUM('professor', 'aluno') NOT NULL,
    id_ref INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_tipo_ref UNIQUE (tipo, id_ref)
);

-- Inserir alguns dados de exemplo para professores
INSERT INTO professores (nome, disciplina) VALUES
('João Silva', 'Matemática'),
('Maria Santos', 'Português'),
('Pedro Oliveira', 'História');

-- Inserir alguns dados de exemplo para alunos
INSERT INTO alunos (nome, idade, turma) VALUES
('Ana Souza', 15, '9º Ano A'),
('Lucas Mendes', 14, '9º Ano A'),
('Julia Santos', 15, '9º Ano B');

-- Inserir algumas notas de exemplo
INSERT INTO notas (aluno_id, professor_id, nota) VALUES
(1, 1, 8.5),
(1, 2, 7.0),
(2, 1, 9.0),
(3, 3, 8.0);
