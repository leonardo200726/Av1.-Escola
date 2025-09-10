#!/usr/bin/env python3
"""
Script para popular o banco de dados com dados de exemplo
Útil para testes e demonstrações do sistema
"""

from av1.database import Database, get_database
from av1.models import Student, Subject, Grade
import random

def create_sample_data():
    """Cria dados de exemplo no sistema"""
    
    print("Inicializando banco de dados...")
    db = Database()
    if not db.create_database():
        print("Erro ao criar banco de dados!")
        return False
    
    # Conectar ao banco
    if not db.connect():
        print("Erro ao conectar ao banco!")
        return False
    
    print("Criando estudantes de exemplo...")
    
    # Lista de nomes de exemplo
    sample_students = [
        ("Ana Silva Santos", "ana.silva@email.com", 12, "6º Ano A"),
        ("Bruno Costa Lima", "bruno.costa@email.com", 13, "7º Ano A"),
        ("Carlos Ferreira", "carlos.ferreira@email.com", 14, "8º Ano A"),
        ("Diana Oliveira", "diana.oliveira@email.com", 13, "7º Ano B"),
        ("Eduardo Santos", "eduardo.santos@email.com", 15, "9º Ano A"),
        ("Fernanda Lima", "fernanda.lima@email.com", 12, "6º Ano B"),
        ("Gabriel Pereira", "gabriel.pereira@email.com", 14, "8º Ano B"),
        ("Helena Costa", "helena.costa@email.com", 16, "9º Ano B"),
        ("Igor Nascimento", "igor.nascimento@email.com", 13, "7º Ano A"),
        ("Julia Rodrigues", "julia.rodrigues@email.com", 15, "9º Ano A"),
        ("Kevin Alves", "kevin.alves@email.com", 12, "6º Ano A"),
        ("Larissa Martins", "larissa.martins@email.com", 14, "8º Ano A"),
        ("Mateus Barbosa", "mateus.barbosa@email.com", 13, "7º Ano B"),
        ("Natalia Souza", "natalia.souza@email.com", 16, "9º Ano B"),
        ("Otavio Mendes", "otavio.mendes@email.com", 12, "6º Ano B")
    ]
    
    students_created = []
    
    for name, email, age, student_class in sample_students:
        student = Student(
            name=name,
            email=email,
            age=age,
            student_class=student_class
        )
        
        if student.save():
            students_created.append(student.id)
            print(f"✓ Estudante criado: {name}")
        else:
            print(f"✗ Erro ao criar: {name}")
    
    print(f"\nTotal de estudantes criados: {len(students_created)}")
    
    # Criar notas de exemplo
    print("\nCriando notas de exemplo...")
    
    subjects = ['MAT', 'POR', 'HIS', 'GEO', 'CIE', 'ING']
    grades_created = 0
    
    for student_id in students_created:
        for subject_id in subjects:
            # Criar notas para alguns bimestres (variado)
            num_periods = random.randint(2, 4)  # Entre 2 e 4 bimestres
            periods_to_create = random.sample(range(1, 5), num_periods)
            
            for period in periods_to_create:
                # Gerar nota realista (com tendência para notas médias)
                base_grade = random.uniform(4.0, 9.5)
                
                # Adicionar alguma variação por matéria
                if subject_id == 'MAT':  # Matemática pode ser mais difícil
                    base_grade = max(3.0, base_grade - 0.5)
                elif subject_id in ['POR', 'HIS']:  # Humanas podem ter notas melhores
                    base_grade = min(10.0, base_grade + 0.3)
                
                # Arredondar para 1 casa decimal
                final_grade = round(base_grade, 1)
                
                grade = Grade(
                    student_id=student_id,
                    subject_id=subject_id,
                    period=period,
                    grade=final_grade
                )
                
                if grade.save():
                    grades_created += 1
    
    print(f"Total de notas criadas: {grades_created}")
    
    print("\n" + "="*50)
    print("🎓 DADOS DE EXEMPLO CRIADOS COM SUCESSO!")
    print("="*50)
    print(f"📊 Estudantes: {len(students_created)}")
    print(f"📝 Notas: {grades_created}")
    print(f"📚 Matérias: 6 (padrão)")
    print("\nO sistema está pronto para uso!")
    print("Inicie o servidor com: python av1.app.py")
    print("="*50)
    
    return True

def reset_database():
    """Remove todos os dados do banco (CUIDADO!)"""
    print("⚠️  ATENÇÃO: Esta operação irá remover TODOS os dados!")
    confirmation = input("Digite 'CONFIRMAR' para prosseguir: ")
    
    if confirmation != 'CONFIRMAR':
        print("Operação cancelada.")
        return
    
    db = get_database()
    if not db:
        print("Erro ao conectar ao banco!")
        return
    
    try:
        # Remover dados na ordem correta (respeitando foreign keys)
        db.execute_query("DELETE FROM grades")
        db.execute_query("DELETE FROM students")
        print("✓ Todos os dados foram removidos.")
        
        # Resetar auto_increment
        db.execute_query("ALTER TABLE students AUTO_INCREMENT = 1")
        db.execute_query("ALTER TABLE grades AUTO_INCREMENT = 1")
        print("✓ Contadores resetados.")
        
    except Exception as e:
        print(f"Erro ao limpar banco: {e}")

def show_stats():
    """Mostra estatísticas atuais do banco"""
    db = get_database()
    if not db:
        print("Erro ao conectar ao banco!")
        return
    
    # Contar estudantes
    result = db.fetch_query("SELECT COUNT(*) as count FROM students")
    students_count = result[0]['count'] if result else 0
    
    # Contar notas
    result = db.fetch_query("SELECT COUNT(*) as count FROM grades")
    grades_count = result[0]['count'] if result else 0
    
    # Contar matérias
    result = db.fetch_query("SELECT COUNT(*) as count FROM subjects")
    subjects_count = result[0]['count'] if result else 0
    
    # Estatísticas de aprovação
    result = db.fetch_query("""
        SELECT 
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT CASE WHEN AVG(g.grade) >= 6 THEN s.id END) as approved,
            AVG(g.grade) as general_average
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id
        GROUP BY ()
    """)
    
    stats = result[0] if result else {}
    
    print("\n" + "="*40)
    print("📊 ESTATÍSTICAS ATUAIS DO SISTEMA")
    print("="*40)
    print(f"👥 Estudantes: {students_count}")
    print(f"📚 Matérias: {subjects_count}")
    print(f"📝 Notas lançadas: {grades_count}")
    
    if stats:
        approved = stats.get('approved', 0) or 0
        total = stats.get('total_students', 0) or 0
        average = stats.get('general_average', 0) or 0
        
        approval_rate = (approved / total * 100) if total > 0 else 0
        
        print(f"✅ Aprovados: {approved}/{total} ({approval_rate:.1f}%)")
        print(f"📈 Média geral: {average:.1f}")
    
    print("="*40)

def main():
    """Função principal do script"""
    print("🛡️  ESCOLA ESCUDO - SISTEMA DE DADOS")
    print("="*50)
    print("1. Criar dados de exemplo")
    print("2. Mostrar estatísticas atuais")
    print("3. Resetar banco de dados (PERIGOSO)")
    print("4. Sair")
    print("-"*50)
    
    while True:
        try:
            choice = input("Escolha uma opção (1-4): ").strip()
            
            if choice == '1':
                create_sample_data()
                break
            elif choice == '2':
                show_stats()
                break
            elif choice == '3':
                reset_database()
                break
            elif choice == '4':
                print("Saindo...")
                break
            else:
                print("Opção inválida. Tente novamente.")
                
        except KeyboardInterrupt:
            print("\nOperação cancelada pelo usuário.")
            break
        except Exception as e:
            print(f"Erro: {e}")
            break

if __name__ == "__main__":
    main()