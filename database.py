import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv

# Carregar variáveis de ambiente
load_dotenv()

class Database:
    def __init__(self):
        self.host = os.getenv('DB_HOST', 'localhost')
        self.database = os.getenv('DB_NAME', 'escola_escudo')
        self.user = os.getenv('DB_USER', 'root')
        self.password = os.getenv('DB_PASSWORD', '')
        self.connection = None
    
    def connect(self):
        """Estabelece conexão com o banco de dados"""
        try:
            self.connection = mysql.connector.connect(
                host=self.host,
                database=self.database,
                user=self.user,
                password=self.password,
                charset='utf8mb4',
                collation='utf8mb4_unicode_ci'
            )
            
            if self.connection.is_connected():
                print(f"Conectado ao MySQL Server versão {self.connection.get_server_info()}")
                return True
                
        except Error as e:
            print(f"Erro ao conectar com MySQL: {e}")
            return False
    
    def disconnect(self):
        """Fecha a conexão com o banco de dados"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            print("Conexão MySQL fechada")
    
    def execute_query(self, query, params=None):
        """Executa uma query de modificação (INSERT, UPDATE, DELETE)"""
        try:
            cursor = self.connection.cursor()
            if params:
                cursor.execute(query, params)
            else:
                cursor.execute(query)
            
            self.connection.commit()
            return cursor.lastrowid if cursor.lastrowid else cursor.rowcount
            
        except Error as e:
            print(f"Erro ao executar query: {e}")
            self.connection.rollback()
            return None
        finally:
            cursor.close()
    
    def fetch_query(self, query, params=None):
        """Executa uma query de consulta (SELECT)"""
        try:
            cursor = self.connection.cursor(dictionary=True)
            if params:
                cursor.execute(query, params)
            else:
                cursor.execute(query)
            
            return cursor.fetchall()
            
        except Error as e:
            print(f"Erro ao executar consulta: {e}")
            return None
        finally:
            cursor.close()
    
    def create_database(self):
        """Cria o banco de dados e as tabelas se não existirem"""
        try:
            # Conectar sem especificar database
            temp_connection = mysql.connector.connect(
                host=self.host,
                user=self.user,
                password=self.password
            )
            
            cursor = temp_connection.cursor()
            
            # Criar database se não existir
            cursor.execute(f"CREATE DATABASE IF NOT EXISTS {self.database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
            cursor.execute(f"USE {self.database}")
            
            # Criar tabelas
            tables = {
                'students': """
                    CREATE TABLE IF NOT EXISTS students (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        age INT NOT NULL CHECK (age BETWEEN 6 AND 18),
                        class VARCHAR(20) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                """,
                'subjects': """
                    CREATE TABLE IF NOT EXISTS subjects (
                        id VARCHAR(3) PRIMARY KEY,
                        name VARCHAR(50) NOT NULL,
                        teacher VARCHAR(100) NOT NULL,
                        hours_per_week INT NOT NULL,
                        min_grade DECIMAL(3,1) DEFAULT 6.0
                    )
                """,
                'grades': """
                    CREATE TABLE IF NOT EXISTS grades (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        student_id INT NOT NULL,
                        subject_id VARCHAR(3) NOT NULL,
                        period INT NOT NULL CHECK (period BETWEEN 1 AND 4),
                        grade DECIMAL(3,1) NOT NULL CHECK (grade BETWEEN 0 AND 10),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_grade (student_id, subject_id, period)
                    )
                """
            }
            
            for table_name, table_sql in tables.items():
                cursor.execute(table_sql)
                print(f"Tabela {table_name} criada/verificada")
            
            # Inserir dados padrão
            self.insert_default_data(cursor)
            
            temp_connection.commit()
            temp_connection.close()
            
            print("Banco de dados configurado com sucesso!")
            return True
            
        except Error as e:
            print(f"Erro ao criar banco de dados: {e}")
            return False
    
    def insert_default_data(self, cursor):
        """Insere dados padrão no banco"""
        try:
            # Verificar se já existem dados
            cursor.execute("SELECT COUNT(*) as count FROM subjects")
            result = cursor.fetchone()
            
            if result[0] == 0:  # Se não há matérias, inserir dados padrão
                subjects_data = [
                    ('MAT', 'Matemática', 'Prof. Ana Silva', 5),
                    ('POR', 'Português', 'Prof. Carlos Santos', 5),
                    ('HIS', 'História', 'Prof. Maria Oliveira', 3),
                    ('GEO', 'Geografia', 'Prof. João Costa', 3),
                    ('CIE', 'Ciências', 'Prof. Rosa Lima', 4),
                    ('ING', 'Inglês', 'Prof. Peter Johnson', 2)
                ]
                
                cursor.executemany(
                    "INSERT INTO subjects (id, name, teacher, hours_per_week) VALUES (%s, %s, %s, %s)",
                    subjects_data
                )
                
                # Dados de exemplo de estudantes
                students_data = [
                    ('João Silva', 'joao@email.com', 12, '6º Ano A'),
                    ('Maria Santos', 'maria@email.com', 13, '7º Ano B'),
                    ('Pedro Costa', 'pedro@email.com', 14, '8º Ano A'),
                    ('Ana Oliveira', 'ana@email.com', 13, '7º Ano A'),
                    ('Carlos Ferreira', 'carlos@email.com', 15, '9º Ano B')
                ]
                
                cursor.executemany(
                    "INSERT INTO students (name, email, age, class) VALUES (%s, %s, %s, %s)",
                    students_data
                )
                
                # Notas de exemplo
                grades_data = [
                    (1, 'MAT', 1, 8.5), (1, 'MAT', 2, 7.0), (1, 'MAT', 3, 8.0), (1, 'MAT', 4, 7.5),
                    (1, 'POR', 1, 9.0), (1, 'POR', 2, 8.5), (1, 'POR', 3, 9.5), (1, 'POR', 4, 8.0),
                    (1, 'HIS', 1, 7.5), (1, 'HIS', 2, 8.0), (1, 'CIE', 1, 6.5), (1, 'CIE', 2, 7.0),
                    (2, 'MAT', 1, 6.5), (2, 'MAT', 2, 7.0), (2, 'MAT', 3, 6.0), (2, 'MAT', 4, 7.5),
                    (2, 'POR', 1, 8.0), (2, 'POR', 2, 8.5), (2, 'POR', 3, 9.0), (2, 'POR', 4, 8.5),
                    (3, 'MAT', 1, 5.5), (3, 'MAT', 2, 6.0), (3, 'POR', 1, 7.0), (3, 'POR', 2, 6.5)
                ]
                
                cursor.executemany(
                    "INSERT INTO grades (student_id, subject_id, period, grade) VALUES (%s, %s, %s, %s)",
                    grades_data
                )
                
                print("Dados padrão inseridos com sucesso!")
                
        except Error as e:
            print(f"Erro ao inserir dados padrão: {e}")

# Singleton instance
db_instance = None

def get_database():
    """Retorna uma instância singleton do banco de dados"""
    global db_instance
    if db_instance is None:
        db_instance = Database()
        if db_instance.connect():
            return db_instance
        else:
            return None
    return db_instance