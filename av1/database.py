# Arquivo movido automaticamente para facilitar os imports

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

# Função utilitária para obter instância do banco

def get_database():
    db = Database()
    db.connect()
    return db
