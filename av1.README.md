# Sistema de Gestão Escolar - Escola Escudo

Sistema completo de gerenciamento escolar desenvolvido com Python Flask (backend) e HTML/CSS/JavaScript (frontend).

## 📋 Funcionalidades

- **Gerenciamento de Alunos**: Cadastro, edição e exclusão de estudantes
- **Controle de Notas**: Lançamento de notas por bimestre e matéria
- **Dashboard**: Estatísticas gerais do sistema
- **Relatórios**: Relatórios de desempenho por aluno e matéria
- **API RESTful**: Backend completo com endpoints para todas as operações

## 🛠️ Tecnologias Utilizadas

### Backend
- Python 3.8+
- Flask (Web Framework)
- MySQL (Banco de Dados)
- Flask-CORS (Cross-Origin Resource Sharing)

### Frontend
- HTML5
- CSS3 (Design moderno e responsivo)
- JavaScript (ES6+)
- Fetch API para comunicação com backend

## 📦 Instalação e Configuração

### 1. Pré-requisitos
- Python 3.8 ou superior
- MySQL Server
- XAMPP (recomendado para facilitar configuração do MySQL)

### 2. Configuração do Ambiente

#### 2.1. Clone ou baixe os arquivos
```bash
# Estrutura de pastas:
projeto/
├── av1.database.py
├── av1.models.py
├── av1.app.py
├── av1.scripts.js
├── av1.styles.css
├── av1.index.html
├── av1.requirements.txt
├── av1.README.md
├── av1.seed.py
└── av1.REPORT.md
```

#### 2.2. Instale as dependências Python
```bash
pip install -r av1.requirements.txt
```

#### 2.3. Configure o banco de dados
1. Inicie o XAMPP e ative MySQL
2. Acesse phpMyAdmin (http://localhost/phpmyadmin)
3. Execute o script de criação do banco (será feito automaticamente na primeira execução)

#### 2.4. Configuração de variáveis de ambiente (opcional)
Crie um arquivo `.env` na raiz do projeto:
```
DB_HOST=localhost
DB_NAME=escola_escudo
DB_USER=root
DB_PASSWORD=
```

### 3. Execução

#### 3.1. Iniciar o servidor backend
```bash
python av1.app.py
```
O servidor estará rodando em: http://localhost:5000

#### 3.2. Abrir o frontend
Abra o arquivo `av1.index.html` em seu navegador ou configure um servidor web local.

## 📊 Estrutura do Banco de Dados

### Tabelas

#### students
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `name` (VARCHAR(100), NOT NULL)
- `email` (VARCHAR(100), UNIQUE, NOT NULL)
- `age` (INT, CHECK 6-18)
- `class` (VARCHAR(20), NOT NULL)
- `created_at`, `updated_at` (TIMESTAMP)

#### subjects
- `id` (VARCHAR(3), PRIMARY KEY)
- `name` (VARCHAR(50), NOT NULL)
- `teacher` (VARCHAR(100), NOT NULL)
- `hours_per_week` (INT, NOT NULL)
- `min_grade` (DECIMAL(3,1), DEFAULT 6.0)

#### grades
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `student_id` (INT, FOREIGN KEY)
- `subject_id` (VARCHAR(3), FOREIGN KEY)
- `period` (INT, CHECK 1-4)
- `grade` (DECIMAL(3,1), CHECK 0-10)
- `created_at`, `updated_at` (TIMESTAMP)

## 🔗 API Endpoints

### Estudantes
- `GET /api/students` - Listar todos os estudantes
- `GET /api/students/{id}` - Buscar estudante por ID
- `POST /api/students` - Criar novo estudante
- `PUT /api/students/{id}` - Atualizar estudante
- `DELETE /api/students/{id}` - Excluir estudante

### Matérias
- `GET /api/subjects` - Listar todas as matérias
- `GET /api/subjects/{id}` - Buscar matéria por ID

### Notas
- `GET /api/grades` - Listar todas as notas
- `GET /api/grades?student_id={id}` - Filtrar por estudante
- `GET /api/grades?subject_id={id}` - Filtrar por matéria
- `POST /api/grades` - Criar/atualizar nota

### Dashboard e Relatórios
- `GET /api/dashboard` - Estatísticas do dashboard
- `GET /api/reports/general` - Estatísticas gerais
- `GET /api/reports/students` - Relatório por estudante
- `GET /api/reports/subjects` - Relatório por matéria

### Utilitários
- `GET /api/health` - Verificar saúde da API
- `POST /api/init` - Inicializar sistema

## 🎨 Características do Design

- **Design Moderno**: Interface dark com gradientes e efeitos visuais
- **Responsivo**: Adaptável a diferentes tamanhos de tela
- **Interativo**: Animações e transições suaves
- **Intuitivo**: Navegação simples e clara

## 📱 Funcionalidades da Interface

### Dashboard
- Estatísticas em tempo real
- Cards informativos com métricas importantes
- Visão geral do sistema

### Gestão de Alunos
- Formulário de cadastro validado
- Tabela com informações e ações
- Cálculo automático de médias e status

### Lançamento de Notas
- Formulário por bimestre
- Filtros por aluno e matéria
- Visualização organizada das notas

### Relatórios
- Estatísticas de aprovação
- Relatório detalhado por aluno
- Análise de desempenho por matéria

## 🚀 Como Usar

### 1. Cadastro de Alunos
1. Acesse a aba "Alunos"
2. Preencha o formulário de cadastro
3. Os dados são salvos automaticamente no banco

### 2. Lançamento de Notas
1. Acesse a aba "Notas"
2. Selecione aluno, matéria, bimestre e nota
3. A nota é salva e médias são calculadas automaticamente

### 3. Visualização de Relatórios
1. Acesse a aba "Relatórios"
2. Visualize estatísticas gerais
3. Analise desempenho individual e por matéria

## 🔧 Troubleshooting

### Problemas Comuns

#### Erro de Conexão com Banco
- Verifique se o MySQL está rodando
- Confirme as credenciais no arquivo de configuração
- Teste a conexão via phpMyAdmin

#### API não responde
- Verifique se o servidor Flask está rodando
- Confirme a porta (padrão: 5000)
- Verifique logs de erro no terminal

#### Frontend não carrega dados
- Verifique console do navegador para erros
- Confirme se a URL da API está correta
- Teste endpoints diretamente no navegador

## 📝 Notas de Desenvolvimento

- Sistema desenvolvido com arquitetura MVC
- Separação clara entre backend e frontend
- Código modular e reutilizável
- Validações tanto no frontend quanto backend
- Tratamento de erros em todas as camadas

## 🔐 Segurança

- Validação de dados de entrada
- Prevenção de SQL Injection com prepared statements
- CORS configurado adequadamente
- Validações de tipo e range nos campos

## 📈 Possíveis Melhorias

- Autenticação de usuários
- Backup automático do banco
- Exportação de relatórios em PDF
- Sistema de notificações
- Dashboard com gráficos interativos
- App mobile responsivo

## 🤝 Contribuição

Este é um projeto educacional. Sugestões e melhorias são bem-vindas!

## 📄 Licença

Projeto desenvolvido para fins educacionais.