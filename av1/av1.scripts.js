// Configuração da API
const API_BASE_URL = 'http://localhost:5000/api';

// Estado global da aplicação
let students = [];
let subjects = [];
let grades = [];

// Inicialização da aplicação
document.addEventListener('DOMContentLoaded', async function() {
    updateDate();
    await initializeSystem();
    setupEventListeners();
});

// Atualizar data atual
function updateDate() {
    const now = new Date();
    const dateStr = now.toLocaleDateString('pt-BR');
    document.getElementById('current-date').textContent = dateStr;
}

// Inicializar sistema
async function initializeSystem() {
    try {
        showLoading();
        
        // Verificar saúde da API
        const health = await fetchAPI('/health');
        if (!health) {
            showAlert('Erro na conexão com o servidor', 'error');
            return;
        }
        
        // Carregar dados
        await loadAllData();
        
        // Atualizar interface
        updateDashboard();
        updateStudentsTable();
        updateGradesTable();
        updateReports();
        
        hideLoading();
        showAlert('Sistema carregado com sucesso!', 'success');
        
    } catch (error) {
        hideLoading();
        console.error('Erro ao inicializar sistema:', error);
        showAlert('Erro ao carregar sistema', 'error');
    }
}

// Carregar todos os dados
async function loadAllData() {
    try {
        const [studentsData, subjectsData, gradesData] = await Promise.all([
            fetchAPI('/students'),
            fetchAPI('/subjects'),
            fetchAPI('/grades')
        ]);
        
        students = studentsData || [];
        subjects = subjectsData || [];
        grades = gradesData || [];
        
        loadStudentOptions();
        
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        throw error;
    }
}

// Função auxiliar para fazer requisições à API
async function fetchAPI(endpoint, options = {}) {
    try {
        const url = API_BASE_URL + endpoint;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erro na requisição');
        }
        
        return data;
        
    } catch (error) {
        console.error('Erro na API:', error);
        throw error;
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Formulário de estudantes
    document.getElementById('student-form').addEventListener('submit', handleStudentSubmit);
    
    // Formulário de notas
    document.getElementById('grades-form').addEventListener('submit', handleGradeSubmit);
    
    // Filtros de notas
    document.getElementById('filter-btn').addEventListener('click', filterGrades);
    document.getElementById('clear-filters-btn').addEventListener('click', clearFilters);
}

// Manipulador de mudança de aba
function switchTab(tabName, buttonElement) {
    // Esconder todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remover classe ativa dos botões
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar aba selecionada
    document.getElementById(tabName).classList.add('active');
    
    // Adicionar classe ativa ao botão clicado
    buttonElement.classList.add('active');

    // Atualizar dados quando necessário
    switch(tabName) {
        case 'dashboard':
            updateDashboard();
            break;
        case 'students':
            updateStudentsTable();
            break;
        case 'grades':
            updateGradesTable();
            break;
        case 'reports':
            updateReports();
            break;
    }
}

// ============ GERENCIAMENTO DE ESTUDANTES ============

async function handleStudentSubmit(e) {
    e.preventDefault();
    
    try {
        const formData = {
            name: document.getElementById('student-name').value,
            email: document.getElementById('student-email').value,
            age: parseInt(document.getElementById('student-age').value),
            class: document.getElementById('student-class').value
        };
        
        const result = await fetchAPI('/students', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        // Limpar formulário
        document.getElementById('student-form').reset();
        
        // Recarregar dados
        await loadAllData();
        updateStudentsTable();
        updateDashboard();
        
        showAlert('Estudante cadastrado com sucesso!', 'success');
        
    } catch (error) {
        showAlert(error.message || 'Erro ao cadastrar estudante', 'error');
    }
}

function loadStudentOptions() {
    const selects = ['grade-student', 'search-student'];
    
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        const currentValue = select.value;
        
        select.innerHTML = selectId === 'search-student' ? 
            '<option value="">Todos os alunos</option>' : 
            '<option value="">Selecione um aluno</option>';
        
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.name;
            select.appendChild(option);
        });
        
        select.value = currentValue;
    });
}

function updateStudentsTable() {
    const tbody = document.getElementById('students-tbody');
    tbody.innerHTML = '';
    
    students.forEach(student => {
        const avg = student.average_grade || 0;
        const status = student.status || 'Sem Notas';
        
        const row = `
            <tr>
                <td>${student.id}</td>
                <td>${student.name}</td>
                <td>${student.email}</td>
                <td>${student.age}</td>
                <td>${student.class}</td>
                <td class="${getGradeClass(avg)}">${avg.toFixed(1)}</td>
                <td class="${getStatusClass(status)}">${status}</td>
                <td>
                    <button class="btn btn-danger btn-small" onclick="deleteStudent(${student.id})">
                        Excluir
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

async function deleteStudent(studentId) {
    if (!confirm('Tem certeza que deseja excluir este aluno?')) {
        return;
    }
    
    try {
        await fetchAPI(`/students/${studentId}`, {
            method: 'DELETE'
        });
        
        // Recarregar dados
        await loadAllData();
        updateStudentsTable();
        updateDashboard();
        updateReports();
        
        showAlert('Estudante excluído com sucesso!', 'success');
        
    } catch (error) {
        showAlert(error.message || 'Erro ao excluir estudante', 'error');
    }
}

// ============ GERENCIAMENTO DE NOTAS ============

async function handleGradeSubmit(e) {
    e.preventDefault();
    
    try {
        const formData = {
            student_id: parseInt(document.getElementById('grade-student').value),
            subject_id: document.getElementById('grade-subject').value,
            period: parseInt(document.getElementById('grade-period').value),
            grade: parseFloat(document.getElementById('grade-value').value)
        };
        
        await fetchAPI('/grades', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        // Limpar formulário
        document.getElementById('grades-form').reset();
        
        // Recarregar dados
        await loadAllData();
        updateGradesTable();
        updateStudentsTable();
        updateDashboard();
        updateReports();
        
        showAlert('Nota lançada com sucesso!', 'success');
        
    } catch (error) {
        showAlert(error.message || 'Erro ao lançar nota', 'error');
    }
}

function updateGradesTable() {
    const tbody = document.getElementById('grades-tbody');
    tbody.innerHTML = '';
    
    // Agrupar notas por aluno e matéria
    const gradesByStudentSubject = {};
    
    grades.forEach(grade => {
        const key = `${grade.student_id}-${grade.subject_id}`;
        if (!gradesByStudentSubject[key]) {
            gradesByStudentSubject[key] = {
                student_name: grade.student_name,
                subject_name: grade.subject_name,
                periods: {}
            };
        }
        gradesByStudentSubject[key].periods[grade.period] = grade.grade;
    });
    
    // Criar linhas da tabela
    Object.values(gradesByStudentSubject).forEach(gradeData => {
        const periods = gradeData.periods;
        const p1 = periods[1] || '-';
        const p2 = periods[2] || '-';
        const p3 = periods[3] || '-';
        const p4 = periods[4] || '-';
        
        // Calcular média
        const validGrades = Object.values(periods).filter(g => g !== undefined);
        const avg = validGrades.length > 0 ? 
            validGrades.reduce((sum, grade) => sum + grade, 0) / validGrades.length : 0;
        
        const status = avg >= 6 ? 'Aprovado' : 
                      avg >= 4 ? 'Recuperação' : 
                      avg > 0 ? 'Reprovado' : 'Sem Notas';
        
        const row = `
            <tr>
                <td>${gradeData.student_name}</td>
                <td>${gradeData.subject_name}</td>
                <td class="${getGradeClass(p1)}">${p1}</td>
                <td class="${getGradeClass(p2)}">${p2}</td>
                <td class="${getGradeClass(p3)}">${p3}</td>
                <td class="${getGradeClass(p4)}">${p4}</td>
                <td class="${getGradeClass(avg)}">${avg > 0 ? avg.toFixed(1) : '-'}</td>
                <td class="${getStatusClass(status)}">${status}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

async function filterGrades() {
    const studentId = document.getElementById('search-student').value;
    const subjectId = document.getElementById('search-subject').value;
    
    try {
        let endpoint = '/grades';
        const params = new URLSearchParams();
        
        if (studentId) params.append('student_id', studentId);
        if (subjectId) params.append('subject_id', subjectId);
        
        if (params.toString()) {
            endpoint += '?' + params.toString();
        }
        
        grades = await fetchAPI(endpoint);
        updateGradesTable();
        
    } catch (error) {
        showAlert(error.message || 'Erro ao filtrar notas', 'error');
    }
}

async function clearFilters() {
    document.getElementById('search-student').value = '';
    document.getElementById('search-subject').value = '';
    
    try {
        grades = await fetchAPI('/grades');
        updateGradesTable();
    } catch (error) {
        showAlert(error.message || 'Erro ao limpar filtros', 'error');
    }
}

// ============ DASHBOARD ============

async function updateDashboard() {
    try {
        const stats = await fetchAPI('/dashboard');
        
        document.getElementById('total-students').textContent = stats.total_students || 0;
        document.getElementById('total-subjects').textContent = stats.total_subjects || 0;
        document.getElementById('passed-students').textContent = stats.passed_students || 0;
        document.getElementById('average-grade').textContent = (stats.average_grade || 0).toFixed(1);
        
    } catch (error) {
        console.error('Erro ao atualizar dashboard:', error);
    }
}

// ============ RELATÓRIOS ============

async function updateReports() {
    try {
        await Promise.all([
            updateGeneralStats(),
            updateStudentReport(),
            updateSubjectReport()
        ]);
    } catch (error) {
        console.error('Erro ao atualizar relatórios:', error);
    }
}

async function updateGeneralStats() {
    try {
        const stats = await fetchAPI('/reports/general');
        
        document.getElementById('report-approved').textContent = stats.approved || 0;
        document.getElementById('report-failed').textContent = stats.failed || 0;
        document.getElementById('report-recovery').textContent = stats.recovery || 0;
        document.getElementById('report-percentage').textContent = (stats.approval_rate || 0) + '%';
        
    } catch (error) {
        console.error('Erro ao atualizar estatísticas gerais:', error);
    }
}

async function updateStudentReport() {
    try {
        const report = await fetchAPI('/reports/students');
        const tbody = document.getElementById('student-report-tbody');
        tbody.innerHTML = '';
        
        report.forEach(student => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td class="${getGradeClass(student.subjects.MAT?.average || 0)}">
                    ${student.subjects.MAT?.average ? student.subjects.MAT.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.subjects.POR?.average || 0)}">
                    ${student.subjects.POR?.average ? student.subjects.POR.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.subjects.HIS?.average || 0)}">
                    ${student.subjects.HIS?.average ? student.subjects.HIS.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.subjects.GEO?.average || 0)}">
                    ${student.subjects.GEO?.average ? student.subjects.GEO.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.subjects.CIE?.average || 0)}">
                    ${student.subjects.CIE?.average ? student.subjects.CIE.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.subjects.ING?.average || 0)}">
                    ${student.subjects.ING?.average ? student.subjects.ING.average.toFixed(1) : '-'}
                </td>
                <td class="${getGradeClass(student.general_average)}">
                    ${student.general_average ? student.general_average.toFixed(1) : '-'}
                </td>
                <td class="${getStatusClass(student.status)}">${student.status}</td>
            `;
            
            tbody.appendChild(row);
        });
        
    } catch (error) {
        console.error('Erro ao atualizar relatório de estudantes:', error);
    }
}

async function updateSubjectReport() {
    try {
        const report = await fetchAPI('/reports/subjects');
        const tbody = document.getElementById('subject-report-tbody');
        tbody.innerHTML = '';
        
        report.forEach(subject => {
            const row = `
                <tr>
                    <td>${subject.name}</td>
                    <td>${subject.total_students}</td>
                    <td class="status-approved">${subject.approved}</td>
                    <td class="status-failed">${subject.failed}</td>
                    <td class="${getGradeClass(subject.class_average)}">
                        ${subject.class_average ? subject.class_average.toFixed(1) : '-'}
                    </td>
                    <td>${subject.approval_rate}%</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
        
    } catch (error) {
        console.error('Erro ao atualizar relatório de matérias:', error);
    }
}

// ============ FUNÇÕES AUXILIARES ============

function getGradeClass(grade) {
    if (grade === '-' || grade === 0) return '';
    if (grade >= 9) return 'grade-excellent';
    if (grade >= 7) return 'grade-good';
    if (grade >= 6) return 'grade-average';
    return 'grade-poor';
}

function getStatusClass(status) {
    switch(status) {
        case 'Aprovado': return 'status-approved';
        case 'Reprovado': return 'status-failed';
        case 'Recuperação': return 'status-recovery';
        default: return '';
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const mainContent = document.querySelector('.main-content');
    mainContent.insertBefore(alertDiv, mainContent.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.className = 'loading';
    loading.innerHTML = `
        <div class="loading-spinner"></div>
        <p>Carregando sistema...</p>
    `;
    
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}