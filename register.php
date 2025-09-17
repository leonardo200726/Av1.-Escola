<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

$conn = new mysqli("localhost", "root", "", "escola");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Buscar lista de professores e alunos não vinculados
$professores = $conn->query("SELECT p.* FROM professores p 
    LEFT JOIN usuarios u ON u.tipo = 'professor' AND u.id_ref = p.id 
    WHERE u.id IS NULL");

$alunos = $conn->query("SELECT a.* FROM alunos a 
    LEFT JOIN usuarios u ON u.tipo = 'aluno' AND u.id_ref = a.id 
    WHERE u.id IS NULL");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $tipo = $_POST['tipo'];
    $id_ref = (int)$_POST['id_ref'];
    
    // Validações
    if ($password !== $confirm_password) {
        $error = "As senhas não coincidem!";
    } else {
        // Verificar se username já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Este nome de usuário já está em uso!";
        } else {
            // Inserir novo usuário
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, tipo, id_ref) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $password_hash, $tipo, $id_ref);
            
            if ($stmt->execute()) {
                $success = "Cadastro realizado com sucesso! <a href='login.php'>Faça login</a>";
            } else {
                $error = "Erro ao cadastrar usuário!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Sistema Escolar</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        h2 { text-align: center; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background-color: #1557b0; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
        .success { color: green; text-align: center; margin-bottom: 10px; }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        a { color: #1a73e8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Cadastro</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirme a senha:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de usuário:</label>
                <select id="tipo" name="tipo" required onchange="updateRefOptions(this.value)">
                    <option value="">Selecione...</option>
                    <option value="professor">Professor</option>
                    <option value="aluno">Aluno</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_ref">Vincular à conta:</label>
                <select id="id_ref" name="id_ref" required>
                    <option value="">Selecione o tipo primeiro...</option>
                </select>
            </div>
            
            <button type="submit">Cadastrar</button>
        </form>
        
        <div class="login-link">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>

    <script>
    // Pre-seleciona tipo se passado via query string
    (function(){
        const params = new URLSearchParams(window.location.search);
        const tipo = params.get('tipo');
        if (tipo === 'aluno' || tipo === 'professor') {
            const sel = document.getElementById('tipo');
            sel.value = tipo;
            updateRefOptions(tipo);
        }
    })();
    function updateRefOptions(tipo) {
        const refSelect = document.getElementById('id_ref');
        refSelect.innerHTML = '<option value="">Selecione...</option>';
        
        if (tipo === 'professor') {
            <?php while($prof = $professores->fetch_assoc()): ?>
            refSelect.innerHTML += `<option value="<?php echo $prof['id']; ?>">
                <?php echo htmlspecialchars($prof['nome'] . ' - ' . $prof['disciplina']); ?>
            </option>`;
            <?php endwhile; ?>
        } else if (tipo === 'aluno') {
            <?php while($aluno = $alunos->fetch_assoc()): ?>
            refSelect.innerHTML += `<option value="<?php echo $aluno['id']; ?>">
                <?php echo htmlspecialchars($aluno['nome'] . ' - ' . $aluno['turma']); ?>
            </option>`;
            <?php endwhile; ?>
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
