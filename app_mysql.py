from flask import Flask, jsonify
import mysql.connector

app = Flask(__name__)

# Rota para buscar todos os alunos
@app.route('/api/alunos')
def get_alunos():
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='escola_escudo'
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM alunos")
    alunos = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(alunos)

# Rota para buscar todas as turmas
@app.route('/api/turmas')
def get_turmas():
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='escola_escudo'
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM turmas")
    turmas = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(turmas)

if __name__ == '__main__':
    app.run(debug=True)
