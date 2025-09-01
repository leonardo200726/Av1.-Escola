from flask import Flask, jsonify, request
try:
    from flask_cors import CORS
except ImportError:
    CORS = lambda app: None
import json
from av1.models import Student, Subject, Grade, Dashboard, Reports
from av1.database import get_database, Database

app = Flask(__name__)
CORS(app)

# Configuração do Flask
app.config['JSON_AS_ASCII'] = False
app.config['JSONIFY_PRETTYPRINT_REGULAR'] = True

@app.before_first_request
def initialize_database():
    """Inicializa o banco de dados na primeira requisição"""
    db = Database()
    db.create_database()

@app.errorhandler(404)
def not_found(error):
    return jsonify({'message': 'Endpoint não encontrado'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'message': 'Erro interno do servidor'}), 500

# ============ ROTAS DOS ESTUDANTES ============


@app.route('/api/students', methods=['GET'])
def get_students():
    """Retorna lista de todos os estudantes do MySQL"""
    try:
        from av1.models import fetch_all_students
        students = fetch_all_students()
        return jsonify(students), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar estudantes: {str(e)}'}), 500

@app.route('/api/students/<int:student_id>', methods=['GET'])
def get_student(student_id):
    """Retorna um estudante específico"""
    try:
        student = Student.get_by_id(student_id)
        if student:
            return jsonify(student), 200
        else:
            return jsonify({'message': 'Estudante não encontrado'}), 404
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar estudante: {str(e)}'}), 500

@app.route('/api/students', methods=['POST'])
def create_student():
    """Cria um novo estudante no MySQL"""
    try:
        data = request.get_json()
        required_fields = ['name', 'email', 'age', 'class']
        for field in required_fields:
            if field not in data or not data[field]:
                return jsonify({'message': f'Campo obrigatório não fornecido: {field}'}), 400
        if not isinstance(data['age'], int) or data['age'] < 6 or data['age'] > 18:
            return jsonify({'message': 'Idade deve estar entre 6 e 18 anos'}), 400
        import mysql.connector
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='escola_escudo'
        )
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO students (name, email, age, student_class) VALUES (%s, %s, %s, %s)",
            (data['name'], data['email'], data['age'], data['class'])
        )
        conn.commit()
        student_id = cursor.lastrowid
        cursor.close()
        conn.close()
        return jsonify({
            'message': 'Estudante criado com sucesso',
            'id': student_id
        }), 201
    except mysql.connector.IntegrityError:
        return jsonify({'message': 'Email já está em uso'}), 400
    except Exception as e:
        return jsonify({'message': f'Erro ao criar estudante: {str(e)}'}), 500

@app.route('/api/students/<int:student_id>', methods=['PUT'])
def update_student(student_id):
    """Atualiza um estudante"""
    try:
        data = request.get_json()
        
        # Buscar estudante existente
        existing_student = Student.get_by_id(student_id)
        if not existing_student:
            return jsonify({'message': 'Estudante não encontrado'}), 404
        
        # Criar objeto estudante com dados atualizados
        student = Student(
            id=student_id,
            name=data.get('name'),
            email=data.get('email'),
            age=data.get('age'),
            student_class=data.get('class')
        )
        
        if student.save():
            return jsonify({'message': 'Estudante atualizado com sucesso'}), 200
        else:
            return jsonify({'message': 'Erro ao atualizar estudante'}), 500
            
    except Exception as e:
        if 'Duplicate entry' in str(e):
            return jsonify({'message': 'Email já está em uso'}), 400
        return jsonify({'message': f'Erro ao atualizar estudante: {str(e)}'}), 500

@app.route('/api/students/<int:student_id>', methods=['DELETE'])
def delete_student(student_id):
    """Remove um estudante"""
    try:
        student = Student(id=student_id)
        
        if student.delete():
            return jsonify({'message': 'Estudante excluído com sucesso'}), 200
        else:
            return jsonify({'message': 'Estudante não encontrado'}), 404
            
    except Exception as e:
        return jsonify({'message': f'Erro ao excluir estudante: {str(e)}'}), 500

# ============ ROTAS DAS MATÉRIAS ============

@app.route('/api/subjects', methods=['GET'])
def get_subjects():
    """Retorna lista de todas as matérias"""
    try:
        subjects = Subject.get_all()
        return jsonify(subjects), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar matérias: {str(e)}'}), 500

@app.route('/api/subjects/<string:subject_id>', methods=['GET'])
def get_subject(subject_id):
    """Retorna uma matéria específica"""
    try:
        subject = Subject.get_by_id(subject_id)
        if subject:
            return jsonify(subject), 200
        else:
            return jsonify({'message': 'Matéria não encontrada'}), 404
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar matéria: {str(e)}'}), 500

# ============ ROTAS DAS NOTAS ============

@app.route('/api/grades', methods=['GET'])
def get_grades():
    """Retorna notas com filtros opcionais"""
    try:
        student_id = request.args.get('student_id', type=int)
        subject_id = request.args.get('subject_id')
        
        if student_id or subject_id:
            grades = Grade.get_by_filters(student_id, subject_id)
        else:
            grades = Grade.get_all()
            
        return jsonify(grades), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar notas: {str(e)}'}), 500

@app.route('/api/grades', methods=['POST'])
def create_grade():
    """Cria ou atualiza uma nota"""
    try:
        data = request.get_json()
        
        # Validar dados obrigatórios
        required_fields = ['student_id', 'subject_id', 'period', 'grade']
        for field in required_fields:
            if field not in data:
                return jsonify({'message': f'Campo obrigatório não fornecido: {field}'}), 400
        
        # Validar valores
        if not (1 <= data['period'] <= 4):
            return jsonify({'message': 'Período deve estar entre 1 e 4'}), 400
            
        if not (0 <= data['grade'] <= 10):
            return jsonify({'message': 'Nota deve estar entre 0 e 10'}), 400
        
        # Criar/atualizar nota
        grade = Grade(
            student_id=data['student_id'],
            subject_id=data['subject_id'],
            period=data['period'],
            grade=data['grade']
        )
        
        if grade.save():
            return jsonify({'message': 'Nota salva com sucesso'}), 201
        else:
            return jsonify({'message': 'Erro ao salvar nota'}), 500
            
    except Exception as e:
        return jsonify({'message': f'Erro ao salvar nota: {str(e)}'}), 500

@app.route('/api/grades/student/<int:student_id>/subject/<string:subject_id>', methods=['GET'])
def get_student_subject_grades(student_id, subject_id):
    """Retorna todas as notas de um aluno em uma matéria"""
    try:
        grades = Grade.get_student_subject_grades(student_id, subject_id)
        average = Grade.calculate_subject_average(student_id, subject_id)
        status = Grade.get_student_status(student_id, subject_id)
        
        return jsonify({
            'grades': grades,
            'average': average,
            'status': status
        }), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar notas: {str(e)}'}), 500

# ============ ROTAS DO DASHBOARD ============

@app.route('/api/dashboard', methods=['GET'])
def get_dashboard():
    """Retorna estatísticas do dashboard"""
    try:
        stats = Dashboard.get_stats()
        return jsonify(stats), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao buscar estatísticas: {str(e)}'}), 500

# ============ ROTAS DOS RELATÓRIOS ============

@app.route('/api/reports/general', methods=['GET'])
def get_general_report():
    """Retorna relatório geral de aprovação"""
    try:
        stats = Reports.get_general_stats()
        return jsonify(stats), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao gerar relatório: {str(e)}'}), 500

@app.route('/api/reports/students', methods=['GET'])
def get_student_report():
    """Retorna relatório detalhado por estudante"""
    try:
        report = Reports.get_student_report()
        return jsonify(report), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao gerar relatório: {str(e)}'}), 500

@app.route('/api/reports/subjects', methods=['GET'])
def get_subject_report():
    """Retorna relatório por matéria"""
    try:
        report = Reports.get_subject_report()
        return jsonify(report), 200
    except Exception as e:
        return jsonify({'message': f'Erro ao gerar relatório: {str(e)}'}), 500

# ============ ROTAS DE UTILITÁRIOS ============

@app.route('/api/health', methods=['GET'])
def health_check():
    """Verifica se a API está funcionando"""
    try:
        db = get_database()
        if db and db.connection.is_connected():
            return jsonify({
                'status': 'OK',
                'message': 'API funcionando corretamente',
                'database': 'Conectado'
            }), 200
        else:
            return jsonify({
                'status': 'ERROR',
                'message': 'Erro na conexão com o banco de dados',
                'database': 'Desconectado'
            }), 500
    except Exception as e:
        return jsonify({
            'status': 'ERROR',
            'message': f'Erro no servidor: {str(e)}',
            'database': 'Erro'
        }), 500

@app.route('/api/init', methods=['POST'])
def initialize_system():
    """Inicializa o sistema criando banco e tabelas"""
    try:
        db = Database()
        if db.create_database():
            return jsonify({'message': 'Sistema inicializado com sucesso'}), 200
        else:
            return jsonify({'message': 'Erro ao inicializar sistema'}), 500
    except Exception as e:
        return jsonify({'message': f'Erro ao inicializar: {str(e)}'}), 500

if __name__ == '__main__':
    # Configurar para desenvolvimento
    app.run(debug=True, host='0.0.0.0', port=5000)