import mysql.connector
from datetime import date

# Conexão com o MySQL do XAMPP
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='escola_escudo'
)
cursor = conn.cursor()

# Criação das tabelas
cursor.execute("""
CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL UNIQUE,
    capacidade INT NOT NULL
)
""")

cursor.execute("""
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    data_nascimento DATE NOT NULL,
    email VARCHAR(120),
    status VARCHAR(10) DEFAULT 'inativo',
    turma_id INT,
    FOREIGN KEY (turma_id) REFERENCES turmas(id)
)
""")

# Dados para inserir
TURMAS = [
    ("1º Ano A", 25),
    ("1º Ano B", 25),
    ("2º Ano A", 30),
    ("3º Ano A", 28)
]

ALUNOS = [
    ("Ana Clara Souza", date(2013, 5, 14), "ana@escola.com", "ativo", 1),
    ("Bruno Henrique", date(2012, 8, 2), "bruno@escola.com", "ativo", 1),
    ("Carla Mendes", date(2014, 1, 20), None, "inativo", None),
    ("Daniel Farias", date(2011, 11, 30), "daniel@escola.com", "ativo", 3),
    ("Eduarda Lima", date(2013, 3, 7), "eduarda@escola.com", "inativo", None)
]

# Inserir turmas
for nome, capacidade in TURMAS:
    cursor.execute("INSERT IGNORE INTO turmas (nome, capacidade) VALUES (%s, %s)", (nome, capacidade))
conn.commit()

# Inserir alunos
for nome, data_nascimento, email, status, turma_id in ALUNOS:
    cursor.execute(
        "INSERT INTO alunos (nome, data_nascimento, email, status, turma_id) VALUES (%s, %s, %s, %s, %s)",
        (nome, data_nascimento, email, status, turma_id)
    )
conn.commit()

cursor.close()
conn.close()
print("Dados inseridos com sucesso no MySQL!")
