<?php
session_start();

$conn = new mysqli("localhost", "root", "", "escola");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// --- Processamento de Login ---
$login_error = '';
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password_hash, tipo, id_ref FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = $row['tipo'];
            $_SESSION['ref_id'] = $row['id_ref'];
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    $login_error = 'Usuário ou senha incorretos';
}

// --- Processamento de Registro ---
$register_error = '';
$register_success = '';
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = $conn->real_escape_string($_POST['reg_username']);
    $password = $_POST['reg_password'];
    $confirm = $_POST['reg_confirm_password'];
    $tipo = $_POST['reg_tipo'];
    $id_ref_raw = $_POST['reg_id_ref']; // can be 'new' or numeric

    if ($password !== $confirm) {
        $register_error = 'As senhas não coincidem.';
    } else {
        // verificar username
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $register_error = 'Nome de usuário já existe.';
        } else {
            // se o usuário escolheu criar novo registro (valor 'new'), insere primeiro
            $id_ref = null;
            if ($id_ref_raw === 'new') {
                if ($tipo === 'aluno') {
                    $new_nome = $conn->real_escape_string($_POST['reg_new_nome']);
                    $new_idade = (int)$_POST['reg_new_idade'];
                    $new_turma = $conn->real_escape_string($_POST['reg_new_turma']);
                    $stmt2 = $conn->prepare("INSERT INTO alunos (nome, idade, turma) VALUES (?, ?, ?)");
                    $stmt2->bind_param('sis', $new_nome, $new_idade, $new_turma);
                    if ($stmt2->execute()) {
                        $id_ref = $stmt2->insert_id;
                    } else {
                        $register_error = 'Erro ao criar registro de aluno.';
                    }
                } else {
                    $new_nome = $conn->real_escape_string($_POST['reg_new_nome']);
                    $new_disc = $conn->real_escape_string($_POST['reg_new_disciplina']);
                    $stmt2 = $conn->prepare("INSERT INTO professores (nome, disciplina) VALUES (?, ?)");
                    $stmt2->bind_param('ss', $new_nome, $new_disc);
                    if ($stmt2->execute()) {
                        $id_ref = $stmt2->insert_id;
                    } else {
                        $register_error = 'Erro ao criar registro de professor.';
                    }
                }
            } else {
                $id_ref = (int)$id_ref_raw;
            }

            if (!$register_error) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, tipo, id_ref) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $username, $hash, $tipo, $id_ref);
                if ($stmt->execute()) {
                    $register_success = 'Cadastro concluído. Faça login.';
                } else {
                    $register_error = 'Erro ao cadastrar usuário.';
                }
            }
        }
    }
}

// --- Processamento de dashboard (inserções/exclusões) ---
if (isset($_SESSION['user_id'])) {
    // Inserções
    if (isset($_POST['add_professor'])) {
        $nome = $conn->real_escape_string($_POST['nome']);
        $disciplina = $conn->real_escape_string($_POST['disciplina']);
        $conn->query("INSERT INTO professores (nome, disciplina) VALUES ('$nome', '$disciplina')");
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
    if (isset($_POST['add_aluno'])) {
        $nome = $conn->real_escape_string($_POST['nome']);
        $idade = (int)$_POST['idade'];
        $turma = $conn->real_escape_string($_POST['turma']);
        $conn->query("INSERT INTO alunos (nome, idade, turma) VALUES ('$nome', '$idade', '$turma')");
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
    if (isset($_POST['add_nota'])) {
        $aluno_id = (int)$_POST['aluno_id'];
        $professor_id = (int)$_POST['professor_id'];
        $nota = (float)$_POST['nota'];
        $conn->query("INSERT INTO notas (aluno_id, professor_id, nota) VALUES ('$aluno_id', '$professor_id', '$nota')");
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
    // Exclusões
    if (isset($_GET['delete_professor'])) {
        $id = (int)$_GET['delete_professor'];
        $conn->query("DELETE FROM professores WHERE id=$id"); header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
    if (isset($_GET['delete_aluno'])) {
        $id = (int)$_GET['delete_aluno'];
        $conn->query("DELETE FROM alunos WHERE id=$id"); header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
    if (isset($_GET['delete_nota'])) {
        $id = (int)$_GET['delete_nota'];
        $conn->query("DELETE FROM notas WHERE id=$id"); header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}

/* ===== Inserções ===== */
if (isset($_POST["add_professor"])) {
    $nome = $conn->real_escape_string($_POST["nome"]);
    $disciplina = $conn->real_escape_string($_POST["disciplina"]);
    $conn->query("INSERT INTO professores (nome, disciplina) VALUES ('$nome', '$disciplina')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST["add_aluno"])) {
    $nome = $conn->real_escape_string($_POST["nome"]);
    $idade = (int)$_POST["idade"];
    $turma = $conn->real_escape_string($_POST["turma"]);
    $conn->query("INSERT INTO alunos (nome, idade, turma) VALUES ('$nome', '$idade', '$turma')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST["add_nota"])) {
    $aluno_id = (int)$_POST["aluno_id"];
    $professor_id = (int)$_POST["professor_id"];
    $nota = (float)$_POST["nota"];
    $conn->query("INSERT INTO notas (aluno_id, professor_id, nota) VALUES ('$aluno_id', '$professor_id', '$nota')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ===== Exclusões ===== */
if (isset($_GET["delete_professor"])) {
    $id = (int)$_GET["delete_professor"];
    $conn->query("DELETE FROM professores WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET["delete_aluno"])) {
    $id = (int)$_GET["delete_aluno"];
    $conn->query("DELETE FROM alunos WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET["delete_nota"])) {
    $id = (int)$_GET["delete_nota"];
    $conn->query("DELETE FROM notas WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Escolar</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        h1 { 
            color: #1a73e8;
            margin: 0;
        }
        h2 { 
            color: #1a73e8;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        button {
            background-color: #1a73e8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background-color: #1557b0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .delete-link {
            color: #dc3545;
            text-decoration: none;
        }
        .delete-link:hover {
            text-decoration: underline;
        }
        .user-info {
            color: #666;
        }
        .user-info a {
            color: #1a73e8;
            text-decoration: none;
            margin-left: 10px;
        }
        .user-info a:hover {
            text-decoration: underline;
        }
        .grid-forms {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Tela pública: boas-vindas + abas Login / Cadastro -->
            <div style="text-align:center; padding:40px 0;">
                <div style="font-size:48px; color:#1a73e8;">🏫</div>
                <h1>Sistema Escolar</h1>
                <p>Faça login ou cadastre-se para acessar o sistema.</p>
            </div>

            <div style="display:flex; gap:40px; justify-content:center;">
                <!-- Login -->
                <div style="width:320px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.06);">
                    <h2 style="margin-top:0;">Entrar</h2>
                    <?php if ($login_error): ?>
                        <div style="color:red; margin-bottom:10px;"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label>Usuário</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit">Entrar</button>
                    </form>
                </div>

                <!-- Cadastro -->
                <div style="width:420px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.06);">
                    <h2 style="margin-top:0;">Cadastrar</h2>
                    <?php if ($register_error): ?>
                        <div style="color:red; margin-bottom:10px;"><?php echo $register_error; ?></div>
                    <?php elseif ($register_success): ?>
                        <div style="color:green; margin-bottom:10px;"><?php echo $register_success; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="form-group">
                            <label>Nome de usuário</label>
                            <input type="text" name="reg_username" required>
                        </div>
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="reg_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirme a senha</label>
                            <input type="password" name="reg_confirm_password" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo de usuário</label>
                            <select name="reg_tipo" id="reg_tipo" required onchange="fetchRefs(this.value)">
                                <option value="">Selecione...</option>
                                <option value="professor">Professor</option>
                                <option value="aluno">Aluno</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vincular a</label>
                            <select name="reg_id_ref" id="reg_id_ref" required>
                                <option value="">Selecione o tipo primeiro...</option>
                            </select>
                        </div>

                        <div id="reg_new_fields" style="display:none; margin-top:10px; padding:10px; border:1px solid #eee; border-radius:6px; background:#fafafa;">
                            <div class="form-group">
                                <label id="reg_new_label_nome">Nome</label>
                                <input type="text" name="reg_new_nome">
                            </div>
                            <div class="form-group">
                                <label id="reg_new_label_2">Idade / Turma ou Disciplina</label>
                                <input type="text" name="reg_new_idade" placeholder="Idade (apenas para aluno)"> 
                                <input type="text" name="reg_new_turma" placeholder="Turma (apenas para aluno)" style="margin-top:6px;">
                                <input type="text" name="reg_new_disciplina" placeholder="Disciplina (apenas para professor)" style="margin-top:6px;">
                            </div>
                        </div>
                        <button type="submit">Cadastrar</button>
                    </form>
                </div>
            </div>

            <script>
            function fetchRefs(tipo) {
                const sel = document.getElementById('reg_id_ref');
                sel.innerHTML = '<option value="">Carregando...</option>';
                fetch('backend/fetch_refs.php?tipo=' + tipo)
                    .then(r => r.json())
                    .then(list => {
                        sel.innerHTML = '<option value="">Selecione...</option>';
                        sel.innerHTML += '<option value="new">Criar novo...</option>';
                        list.forEach(item => {
                            sel.innerHTML += `<option value="${item.id}">${item.label}</option>`;
                        });
                    })
                    .catch(()=>{ sel.innerHTML = '<option value="">Erro</option>' });
                toggleNewFields(false);
            }

            function toggleNewFields(show) {
                const container = document.getElementById('reg_new_fields');
                if (!container) return;
                container.style.display = show ? 'block' : 'none';
            }

            document.addEventListener('change', function(e){
                if (e.target && e.target.id === 'reg_id_ref'){
                    toggleNewFields(e.target.value === 'new');
                    // adjust labels depending on type
                    const tipo = document.getElementById('reg_tipo').value;
                    const lblNome = document.getElementById('reg_new_label_nome');
                    const lbl2 = document.getElementById('reg_new_label_2');
                    if (tipo === 'aluno'){
                        lblNome.textContent = 'Nome do aluno';
                        lbl2.textContent = 'Idade e Turma (separe por \' / \' )';
                    } else {
                        lblNome.textContent = 'Nome do professor';
                        lbl2.textContent = 'Disciplina';
                    }
                }
                if (e.target && e.target.id === 'reg_tipo'){
                    // reset ref select when type changes
                    const sel = document.getElementById('reg_id_ref');
                    sel.innerHTML = '<option value="">Selecione o tipo primeiro...</option>';
                    toggleNewFields(false);
                }
            });
            </script>

        <?php else: ?>
            <!-- Dashboard para usuário autenticado -->
            <div class="header">
                <h1>Sistema Escolar</h1>
                <div class="user-info">
                    Bem-vindo(a), <?php
                        if ($_SESSION['user_type'] == 'professor') {
                            $stmt = $conn->prepare("SELECT nome FROM professores WHERE id = ?");
                        } else {
                            $stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
                        }
                        $stmt->bind_param("i", $_SESSION['ref_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                        echo htmlspecialchars($user['nome']);
                    ?>
                    <a href="logout.php">Sair</a>
                </div>
            </div>
            <div class="grid-forms">
                <!-- Formulários rápidos -->
                <div class="form-section">
                    <h2>Cadastrar Professor</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="prof-nome">Nome</label>
                            <input type="text" id="prof-nome" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="prof-disciplina">Disciplina</label>
                            <input type="text" id="prof-disciplina" name="disciplina" required>
                        </div>
                        <button type="submit" name="add_professor">Cadastrar Professor</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2>Cadastrar Aluno</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="aluno-nome">Nome</label>
                            <input type="text" id="aluno-nome" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="aluno-idade">Idade</label>
                            <input type="number" id="aluno-idade" name="idade" required>
                        </div>
                        <div class="form-group">
                            <label for="aluno-turma">Turma</label>
                            <input type="text" id="aluno-turma" name="turma" required>
                        </div>
                        <button type="submit" name="add_aluno">Cadastrar Aluno</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2>Lançar Nota</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="nota-aluno">Aluno</label>
                            <select id="nota-aluno" name="aluno_id" required>
                                <option value="">Selecione...</option>
                                <?php
                                $alunos = $conn->query("SELECT * FROM alunos ORDER BY nome");
                                while ($r = $alunos->fetch_assoc()) {
                                    echo "<option value='".$r['id']."'>".htmlspecialchars($r['nome'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nota-professor">Professor</label>
                            <select id="nota-professor" name="professor_id" required>
                                <option value="">Selecione...</option>
                                <?php
                                $profs = $conn->query("SELECT * FROM professores ORDER BY nome");
                                while ($r = $profs->fetch_assoc()) {
                                    echo "<option value='".$r['id']."'>".htmlspecialchars($r['nome'])." - ".htmlspecialchars($r['disciplina'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nota-valor">Nota</label>
                            <input type="number" id="nota-valor" name="nota" step="0.1" min="0" max="10" required>
                        </div>
                        <button type="submit" name="add_nota">Lançar Nota</button>
                    </form>
                </div>
            </div>

            <!-- Listagens -->
            <h2>Lista de Professores</h2>
            <table>
                <thead>
                    <tr><th>Nome</th><th>Disciplina</th><th>Ação</th></tr>
                </thead>
                <tbody>
                    <?php
                    $professores = $conn->query("SELECT * FROM professores ORDER BY nome");
                    while ($row = $professores->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($row['nome']).'</td>';
                        echo '<td>'.htmlspecialchars($row['disciplina']).'</td>';
                        echo '<td><a class="delete-link" href="?delete_professor='.$row['id'].'">Excluir</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <h2>Lista de Alunos</h2>
            <table>
                <thead>
                    <tr><th>Nome</th><th>Idade</th><th>Turma</th><th>Ação</th></tr>
                </thead>
                <tbody>
                    <?php
                    $alunos = $conn->query("SELECT * FROM alunos ORDER BY nome");
                    while ($row = $alunos->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($row['nome']).'</td>';
                        echo '<td>'.(int)$row['idade'].'</td>';
                        echo '<td>'.htmlspecialchars($row['turma']).'</td>';
                        echo '<td><a class="delete-link" href="?delete_aluno='.$row['id'].'">Excluir</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <h2>Histórico de Notas</h2>
            <table>
                <thead>
                    <tr><th>Aluno</th><th>Professor</th><th>Disciplina</th><th>Nota</th><th>Ação</th></tr>
                </thead>
                <tbody>
                    <?php
                    $notas = $conn->query("SELECT n.id, n.nota, a.nome AS aluno, p.nome AS professor, p.disciplina
                                           FROM notas n
                                           JOIN alunos a ON n.aluno_id = a.id
                                           JOIN professores p ON n.professor_id = p.id
                                           ORDER BY a.nome");
                    while ($row = $notas->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($row['aluno']).'</td>';
                        echo '<td>'.htmlspecialchars($row['professor']).'</td>';
                        echo '<td>'.htmlspecialchars($row['disciplina']).'</td>';
                        echo '<td>'.htmlspecialchars($row['nota']).'</td>';
                        echo '<td><a class="delete-link" href="?delete_nota='.$row['id'].'">Excluir</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
