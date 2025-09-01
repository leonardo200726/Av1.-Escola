import mysql.connector

def get_mysql_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='escola_escudo'
    )

def fetch_all_students():
    conn = get_mysql_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM students")
    alunos = cursor.fetchall()
    cursor.close()
    conn.close()
    return alunos
import sys
sys.path.append('c:/Users/regin/leonardoav1/Av1.-Escola/av1')
from database import get_database
from typing import List, Dict, Optional
import mysql.connector

class Student:
    def __init__(self, id=None, name=None, email=None, age=None, student_class=None):
        self.id = id
        self.name = name
        self.email = email
        self.age = age
        self.student_class = student_class
        self.db = get_database()
    
    @staticmethod
    def get_all():
        """Retorna todos os estudantes com suas médias"""
        db = get_database()
        if not db:
            return []
        
        query = """
        SELECT s.*, 
               COALESCE(AVG(g.grade), 0) as average_grade,
               CASE 
                  WHEN AVG(g.grade) >= 6 THEN 'Aprovado'
                  WHEN AVG(g.grade) >= 4 THEN 'Recuperação'
                  WHEN AVG(g.grade) > 0 THEN 'Reprovado'
                  ELSE 'Sem Notas'
               END as status
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id
        GROUP BY s.id
        ORDER BY s.name
        """
        
        return db.fetch_query(query) or []
    
    @staticmethod
    def get_by_id(student_id: int):
        """Retorna um estudante pelo ID"""
        db = get_database()
        if not db:
            return None
        
        query = "SELECT * FROM students WHERE id = %s"
        result = db.fetch_query(query, (student_id,))
        
        return result[0] if result else None
    
    def save(self):
        """Salva o estudante no banco de dados"""
        if not self.db:
            return False
        
        if self.id:  # Update
            query = """
            UPDATE students 
            SET name = %s, email = %s, age = %s, class = %s 
            WHERE id = %s
            """
            result = self.db.execute_query(
                query, 
                (self.name, self.email, self.age, self.student_class, self.id)
            )
            return result is not None
        else:  # Insert
            query = """
            INSERT INTO students (name, email, age, class) 
            VALUES (%s, %s, %s, %s)
            """
            result = self.db.execute_query(
                query, 
                (self.name, self.email, self.age, self.student_class)
            )
            if result:
                self.id = result
                return True
            return False
    
    def delete(self):
        """Remove o estudante do banco de dados"""
        if not self.db or not self.id:
            return False
        
        query = "DELETE FROM students WHERE id = %s"
        result = self.db.execute_query(query, (self.id,))
        return result is not None
    
    def get_grades(self):
        """Retorna todas as notas do estudante"""
        if not self.db or not self.id:
            return []
        
        query = """
        SELECT g.*, s.name as subject_name
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.student_id = %s
        ORDER BY s.name, g.period
        """
        
        return self.db.fetch_query(query, (self.id,)) or []
    
    def get_average(self):
        """Calcula a média geral do estudante"""
        if not self.db or not self.id:
            return 0.0
        
        query = "SELECT AVG(grade) as average FROM grades WHERE student_id = %s"
        result = self.db.fetch_query(query, (self.id,))
        
        return float(result[0]['average']) if result and result[0]['average'] else 0.0
    
    def to_dict(self):
        """Converte o objeto para dicionário"""
        return {
            'id': self.id,
            'name': self.name,
            'email': self.email,
            'age': self.age,
            'class': self.student_class,
            'average_grade': self.get_average()
        }


class Subject:
    def __init__(self, id=None, name=None, teacher=None, hours_per_week=None, min_grade=6.0):
        self.id = id
        self.name = name
        self.teacher = teacher
        self.hours_per_week = hours_per_week
        self.min_grade = min_grade
        self.db = get_database()
    
    @staticmethod
    def get_all():
        """Retorna todas as matérias"""
        db = get_database()
        if not db:
            return []
        
        query = "SELECT * FROM subjects ORDER BY name"
        return db.fetch_query(query) or []
    
    @staticmethod
    def get_by_id(subject_id: str):
        """Retorna uma matéria pelo ID"""
        db = get_database()
        if not db:
            return None
        
        query = "SELECT * FROM subjects WHERE id = %s"
        result = db.fetch_query(query, (subject_id,))
        
        return result[0] if result else None
    
    def get_class_average(self):
        """Calcula a média da turma na matéria"""
        if not self.db or not self.id:
            return 0.0
        
        query = "SELECT AVG(grade) as average FROM grades WHERE subject_id = %s"
        result = self.db.fetch_query(query, (self.id,))
        
        return float(result[0]['average']) if result and result[0]['average'] else 0.0
    
    def get_approval_stats(self):
        """Retorna estatísticas de aprovação da matéria"""
        if not self.db or not self.id:
            return {'total': 0, 'approved': 0, 'failed': 0, 'approval_rate': 0}
        
        query = """
        SELECT 
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT CASE WHEN AVG(g.grade) >= 6 THEN s.id END) as approved,
            COUNT(DISTINCT CASE WHEN AVG(g.grade) < 6 AND AVG(g.grade) > 0 THEN s.id END) as failed
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = %s
        GROUP BY g.subject_id
        """
        
        result = self.db.fetch_query(query, (self.id,))
        
        if result and result[0]:
            stats = result[0]
            total = stats['total_students']
            approved = stats['approved'] or 0
            failed = stats['failed'] or 0
            approval_rate = (approved / total * 100) if total > 0 else 0
            
            return {
                'total': total,
                'approved': approved,
                'failed': failed,
                'approval_rate': round(approval_rate, 1)
            }
        
        return {'total': 0, 'approved': 0, 'failed': 0, 'approval_rate': 0}
    
    def to_dict(self):
        """Converte o objeto para dicionário"""
        return {
            'id': self.id,
            'name': self.name,
            'teacher': self.teacher,
            'hours_per_week': self.hours_per_week,
            'min_grade': self.min_grade,
            'class_average': self.get_class_average(),
            'approval_stats': self.get_approval_stats()
        }


class Grade:
    def __init__(self, id=None, student_id=None, subject_id=None, period=None, grade=None):
        self.id = id
        self.student_id = student_id
        self.subject_id = subject_id
        self.period = period
        self.grade = grade
        self.db = get_database()
    
    @staticmethod
    def get_all():
        """Retorna todas as notas com informações do aluno e matéria"""
        db = get_database()
        if not db:
            return []
        
        query = """
        SELECT g.*, s.name as student_name, sub.name as subject_name
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN subjects sub ON g.subject_id = sub.id
        ORDER BY s.name, sub.name, g.period
        """
        
        return db.fetch_query(query) or []
    
    @staticmethod
    def get_by_filters(student_id=None, subject_id=None):
        """Retorna notas filtradas por estudante e/ou matéria"""
        db = get_database()
        if not db:
            return []
        
        query = """
        SELECT g.*, s.name as student_name, sub.name as subject_name
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN subjects sub ON g.subject_id = sub.id
        """
        
        conditions = []
        params = []
        
        if student_id:
            conditions.append("g.student_id = %s")
            params.append(student_id)
        
        if subject_id:
            conditions.append("g.subject_id = %s")
            params.append(subject_id)
        
        if conditions:
            query += " WHERE " + " AND ".join(conditions)
        
        query += " ORDER BY s.name, sub.name, g.period"
        
        return db.fetch_query(query, tuple(params)) or []
    
    @staticmethod
    def get_student_subject_grades(student_id: int, subject_id: str):
        """Retorna todas as notas de um aluno em uma matéria específica"""
        db = get_database()
        if not db:
            return []
        
        query = """
        SELECT * FROM grades 
        WHERE student_id = %s AND subject_id = %s 
        ORDER BY period
        """
        
        return db.fetch_query(query, (student_id, subject_id)) or []
    
    def save(self):
        """Salva a nota no banco de dados (insert ou update)"""
        if not self.db:
            return False
        
        # Verificar se já existe uma nota para este aluno, matéria e período
        existing_query = """
        SELECT id FROM grades 
        WHERE student_id = %s AND subject_id = %s AND period = %s
        """
        
        existing = self.db.fetch_query(
            existing_query, 
            (self.student_id, self.subject_id, self.period)
        )
        
        if existing:  # Update
            query = """
            UPDATE grades 
            SET grade = %s, updated_at = CURRENT_TIMESTAMP 
            WHERE student_id = %s AND subject_id = %s AND period = %s
            """
            result = self.db.execute_query(
                query, 
                (self.grade, self.student_id, self.subject_id, self.period)
            )
            self.id = existing[0]['id']
        else:  # Insert
            query = """
            INSERT INTO grades (student_id, subject_id, period, grade) 
            VALUES (%s, %s, %s, %s)
            """
            result = self.db.execute_query(
                query, 
                (self.student_id, self.subject_id, self.period, self.grade)
            )
            if result:
                self.id = result
        
        return result is not None
    
    @staticmethod
    def calculate_subject_average(student_id: int, subject_id: str):
        """Calcula a média de um aluno em uma matéria específica"""
        grades = Grade.get_student_subject_grades(student_id, subject_id)
        
        if not grades:
            return 0.0
        
        total = sum(float(grade['grade']) for grade in grades)
        return round(total / len(grades), 1)
    
    @staticmethod
    def get_student_status(student_id: int, subject_id: str):
        """Determina o status do aluno na matéria"""
        average = Grade.calculate_subject_average(student_id, subject_id)
        
        if average >= 6:
            return 'Aprovado'
        elif average >= 4:
            return 'Recuperação'
        elif average > 0:
            return 'Reprovado'
        else:
            return 'Sem Notas'
    
    def to_dict(self):
        """Converte o objeto para dicionário"""
        return {
            'id': self.id,
            'student_id': self.student_id,
            'subject_id': self.subject_id,
            'period': self.period,
            'grade': self.grade
        }


class Dashboard:
    @staticmethod
    def get_stats():
        """Retorna estatísticas para o dashboard"""
        db = get_database()
        if not db:
            return {}
        
        stats = {}
        
        # Total de estudantes
        query = "SELECT COUNT(*) as total FROM students"
        result = db.fetch_query(query)
        stats['total_students'] = result[0]['total'] if result else 0
        
        # Total de matérias
        query = "SELECT COUNT(*) as total FROM subjects"
        result = db.fetch_query(query)
        stats['total_subjects'] = result[0]['total'] if result else 0
        
        # Estatísticas de aprovação
        query = """
        SELECT s.id, AVG(g.grade) as avg_grade
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id
        GROUP BY s.id
        HAVING avg_grade IS NOT NULL
        """
        
        result = db.fetch_query(query)
        
        if result:
            approved = 0
            total_avg = 0
            students_with_grades = len(result)
            
            for student in result:
                avg = float(student['avg_grade'])
                total_avg += avg
                if avg >= 6:
                    approved += 1
            
            stats['passed_students'] = approved
            stats['average_grade'] = round(total_avg / students_with_grades, 1) if students_with_grades > 0 else 0
        else:
            stats['passed_students'] = 0
            stats['average_grade'] = 0
        
        return stats


class Reports:
    @staticmethod
    def get_general_stats():
        """Retorna estatísticas gerais de aprovação"""
        db = get_database()
        if not db:
            return {'approved': 0, 'failed': 0, 'recovery': 0, 'approval_rate': 0}
        
        query = """
        SELECT s.id, s.name, AVG(g.grade) as avg_grade
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id
        GROUP BY s.id
        """
        
        result = db.fetch_query(query)
        
        if not result:
            return {'approved': 0, 'failed': 0, 'recovery': 0, 'approval_rate': 0}
        
        approved = 0
        failed = 0
        recovery = 0
        
        for student in result:
            avg = float(student['avg_grade']) if student['avg_grade'] else 0
            
            if avg >= 6:
                approved += 1
            elif avg >= 4:
                recovery += 1
            elif avg > 0:
                failed += 1
        
        total = approved + failed + recovery
        approval_rate = round((approved / total) * 100, 1) if total > 0 else 0
        
        return {
            'approved': approved,
            'failed': failed,
            'recovery': recovery,
            'approval_rate': approval_rate
        }
    
    @staticmethod
    def get_student_report():
        """Retorna relatório detalhado por estudante"""
        db = get_database()
        if not db:
            return []
        
        # Buscar todos os estudantes
        students = Student.get_all()
        subjects = Subject.get_all()
        
        report = []
        
        for student in students:
            student_data = {
                'id': student['id'],
                'name': student['name'],
                'class': student['class'],
                'subjects': {},
                'general_average': 0,
                'status': 'Sem Notas'
            }
            
            total_avg = 0
            subject_count = 0
            
            for subject in subjects:
                avg = Grade.calculate_subject_average(student['id'], subject['id'])
                student_data['subjects'][subject['id']] = {
                    'name': subject['name'],
                    'average': avg
                }
                
                if avg > 0:
                    total_avg += avg
                    subject_count += 1
            
            if subject_count > 0:
                general_avg = round(total_avg / subject_count, 1)
                student_data['general_average'] = general_avg
                
                if general_avg >= 6:
                    student_data['status'] = 'Aprovado'
                elif general_avg >= 4:
                    student_data['status'] = 'Recuperação'
                else:
                    student_data['status'] = 'Reprovado'
            
            report.append(student_data)
        
        return report
    
    @staticmethod
    def get_subject_report():
        """Retorna relatório por matéria"""
        subjects = Subject.get_all()
        
        report = []
        
        for subject_data in subjects:
            subject = Subject(**subject_data)
            stats = subject.get_approval_stats()
            
            report.append({
                'id': subject.id,
                'name': subject.name,
                'teacher': subject.teacher,
                'total_students': stats['total'],
                'approved': stats['approved'],
                'failed': stats['failed'],
                'class_average': subject.get_class_average(),
                'approval_rate': stats['approval_rate']
            })
        
        return report